<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Notifications\SubscriptionActiveNotification;
use App\Notifications\SubscriptionPaymentFailedNotification;
use App\Notifications\SubscriptionPlanChangedNotification;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $request->validate([
            'event' => ['required', 'string'],
            'gateway_id' => ['required', 'string'],
        ]);
    
        $subscription = Subscription::where('gateway_id', $request->gateway_id)->firstOrFail();
        
        if ($request->event === 'invoice.payment_failed') {
            $subscription->update(['status' => 'inactive']);
            
            $subscription->user->notify(new SubscriptionPaymentFailedNotification());

            return response()->json(['message' => 'Payment failure handled']);
        }

        if ($request->event === 'invoice.paid') {
            
            if ($subscription->status === 'active') {
                $newEndsAt = $subscription->ends_at->copy();

                $subscription->plan->period === 'yearly'
                    ? $newEndsAt->addYear()
                    : $newEndsAt->addMonth();
                
                $subscription->update(['ends_at' => $newEndsAt]);

                return response()->json(['message' => 'Subscription renewed']);
            }

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

            return response()->json(['message' => 'Subscription activated/swapped'], 200);
        }
        
        return response()->json(['message' => 'Event ignored']);
    }
}
