<?php

namespace App\Interfaces;

use App\Models\User;
use App\Models\Plan;

interface PaymentGatewayInterface
{
    public function createPaymentIntent(User $user, Plan $plan, int $price): array;
}
