<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway; 
    }

    public function store(Request $request)
    {
        $request->validate(['plan_id' => ['required', 'exists:plans,id']]);

        $user = $request->user();

        if ($user->subscriptions()->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'Você já possui uma assinatura ativa.'
            ], 409);
        }

        $plan = Plan::findOrFail($request->plan_id);

        $gatewayResult = $this->paymentGateway->createPaymentIntent($user, $plan, $plan->price);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway_id' => $gatewayResult['gateway_id'],
            'status' => 'pending',
            'locked_price' => $plan->price,
        ]);

        return response()->json([
            'message' => 'Inscrição iniciada. Prossiga para o pagamento.',
            'subscription_id' => $subscription->id,
            'payment_url' => $gatewayResult['payment_url'],
        ], 201);
    }
}
