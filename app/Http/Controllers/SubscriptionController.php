<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Interfaces\PaymentGatewayInterface;
use App\Notifications\SubscriptionCancelledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function cancel(Request $request)
    {
        $user = $request->user();

        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Nenhuma assinatura ativa encontrada para cancelar.'], 404);
        }

        try {
            $this->paymentGateway->cancelSubscription($subscription->gateway_id);
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar no gateway: ' . $e->getMessage());
        }

        $subscription->update(['auto_renew' => false]);

        $user->notify(new SubscriptionCancelledNotification());

        return response()->json(['message' => 'Renovação automática cancelada. Seu acesso continua até o fim do período.'], 200);
    }
}
