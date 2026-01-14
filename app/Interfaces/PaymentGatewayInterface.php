<?php

namespace App\Interfaces;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;

interface PaymentGatewayInterface
{
    public function createPaymentIntent(User $user, Plan $plan, int $price): array;

    public function swapSubscription(User $user, Plan $newPlan, Subscription $currentSubscription): array;
}
