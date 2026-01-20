<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\WebhookLog;
use App\Models\Invoice;
use App\Notifications\SubscriptionActiveNotification;
use App\Notifications\SubscriptionPaymentFailedNotification;
use App\Notifications\SubscriptionPlanChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $request->validate([
            'event' => ['required', 'string'],
            'gateway_id' => ['required', 'string'],
            'webhook_id' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'amount_paid' => ['required', 'numeric'],
            'payment_method' => ['required', 'string'],
        ]);

        $log = WebhookLog::firstOrCreate(
            ['gateway_event_id' => $request->webhook_id],
            [
                'event_type' => $request->event,
                'payload' => $request->all(),
                'status' => 'pending',
            ]
        );

        if ($log->status === 'processed') {
            return response()->json(['message' => 'Event already processed'], 200);
        }

        DB::beginTransaction();

        try {

            $subscription = Subscription::where('gateway_id', $request->gateway_id)->firstOrFail();
            
            if ($request->event === 'invoice.payment_failed') {
                $subscription->update(['status' => 'inactive']);
                $subscription->user->notify(new SubscriptionPaymentFailedNotification());
            }

            if ($request->event === 'invoice.paid') {

                if ($subscription->status === 'active') {
                    $newEndsAt = $subscription->ends_at->copy();

                    $subscription->plan->period === 'yearly'
                        ? $newEndsAt->addYear()
                        : $newEndsAt->addMonth();

                    $subscription->update(['ends_at' => $newEndsAt]);
                
                } else {
                    $oldSubscription = Subscription::where('user_id', $subscription->user_id)
                        ->where('status', 'active')
                        ->first();
                    
                    $isSwap = $oldSubscription !== null;

                    if ($isSwap) {
                        $oldSubscription->update([
                            'status' => 'cancelled',
                            'ends_at' => now(),
                            'auto_renew' => false,
                        ]);
                    }

                    $startedAt = now();
                    $endsAt = $subscription->plan->period === 'yearly'
                        ? $startedAt->copy()->addYear()->endOfDay()
                        : $startedAt->copy()->addMonth()->endOfDay();

                    $attributes = [
                        'status' => 'active',
                        'ends_at' => $endsAt, 
                    ];

                    if ($subscription->status !== 'inactive') { 
                        $attributes['started_at'] = $startedAt;
                    }

                    $subscription->update($attributes);

                    if ($isSwap) {
                        $subscription->user->notify(new SubscriptionPlanChangedNotification());
                    } else {
                        $subscription->user->notify(new SubscriptionActiveNotification());
                    }
                }

                Invoice::create([
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->id,
                    'transaction_id' => $request->transaction_id,
                    'amount' => (int) ($request->amount_paid * 100),
                    'status' => 'paid',
                    'payment_method' => $request->payment_method,
                    'paid_at' => now(),
                    'due_at' => now(),
                ]);
            }

            $log->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Webhook Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }
}