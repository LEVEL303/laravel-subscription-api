<?php

namespace Tests\Feature\Subscriptions;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionTrialTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanStartTrialSubscription()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $plan = Plan::factory()->create(['trial_days' => 7]);

        $response = $this->postJson(route('subscriptions.store'), [
            'plan_id' => $plan->id
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Período de teste iniciado com sucesso!'
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'payment_url' => null,
            'status' => 'active',
            'auto_renew' => false,
        ]);
    }

    public function testUserCannotGetTrialTwice()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
        $plan = Plan::factory()->create(['trial_days' => 7]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'expired'
        ]);

        $response = $this->postJson(route('subscriptions.store'), [
            'plan_id' => $plan->id
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['subscription_id', 'payment_url']);
        $response->assertJson([
            'message' => 'Inscrição iniciada. Prossiga para o pagamento.'
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'auto_renew' => true,
        ]);
    }
}
