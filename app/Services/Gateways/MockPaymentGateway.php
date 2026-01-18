<?php

namespace App\Services\Gateways;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function createPaymentIntent(User $user, Plan $plan, int $price): array
    {
        return [
            'gateway_id' => 'tx' . Str::random(15),
            'payment_url' => 'https://mock-gateway.com/pay/' . Str::random(10), 
        ];
    }

    public function swapSubscription(User $user, Plan $newPlan, Subscription $currentSubscription): array
    {
        return [
            'gateway_id' => 'tx_swap_' . Str::random(15),
            'payment_url' => 'https://mock-gateway.com/swap/' . Str::random(10),
            'amount_to_pay' => $newPlan->price,
        ];
    }

    public function cancelSubscription(string $gatewayId): void
    {
        return;
    }

    public function startTrial(User $user, Plan $plan): string
    {
        return 'trial_' . Str::random(15);
    }
}