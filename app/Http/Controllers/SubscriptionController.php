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

    public function swap(Request $request)
    {   
        $request->validate(['plan_id' => ['required', 'exists:plans,id']]);
        $user = $request->user();

        $currentSubscription = $user->subscriptions()->where('status', 'active')->first();

        if (!$currentSubscription) {
            return response()->json(['message' => 'Não há assinatura ativa para alterar.'], 404);
        }

        if ($currentSubscription->id === $request->plan_id) {
            return response()->json(['message', 'Você já está neste plano.'], 422);
        }

        $newPlan = Plan::findOrFail($request->plan_id);

        $gatewayResult = $this->paymentGateway->swapSubscription($user, $newPlan, $currentSubscription);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'gateway_id' => $gatewayResult['gateway_id'],
            'status' => 'pending',
            'locked_price' => $gatewayResult['amount_to_pay'],
        ]);

        return response()->json([
            'message' => 'Troca de plano iniciada. Realize o pagamento para concluir.',
            'payment_url' => $gatewayResult['payment_url'],
        ], 200);
    }
}
