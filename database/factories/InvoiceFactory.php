<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_id' => Subscription::factory(),
            'transaction_id' => 'inv_' . Str::random(15),
            'amount' => fake()->numberBetween(1000, 9990),
            'status' => 'paid',
            'payment_method' => 'credit_card',
            'paid_at' => now(),
            'due_at' => now(),
        ];
    }
}
