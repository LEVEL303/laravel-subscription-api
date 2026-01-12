<?php

namespace App\Services\Gateways;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function createPaymentIntent(User $user, Plan $plan, int $price): array
    {
        return [
            'gateway_id' => 'tx' . Str::random(15),
            'payment_url' => 'https://fake-gateway.com/pay/'. Str::random(10), 
        ];
    }
}