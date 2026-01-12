<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Notifications\SubscriptionActiveNotification;
use App\Notifications\SubscriptionPaymentFailedNotification;
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
            $subscription->user->notify(new SubscriptionPaymentFailedNotification());

            return response()->json(['message' => 'Payment failure handled']);
        }

        if ($request->event === 'invoice.paid') {
            $startDate = now();
            $endsDate = $subscription->plan->period === 'yearly'
                ? $startDate->copy()->addYear()
                : $startDate->copy()->addMonth();

            $subscription->update([
                'status' => 'active',
                'started_at' => $startDate,
                'ends_at' => $endsDate, 
            ]);

            $subscription->user->notify(new SubscriptionActiveNotification());

            return response()->json(['message' => 'Webhook processed'], 200);
        }
        
        return response()->json(['message' => 'Event ignored']);
    }
}
