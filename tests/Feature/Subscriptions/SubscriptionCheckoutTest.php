<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthenticatedUserCanInitiateCheckout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $plan = Plan::factory()->create([
            'price' => 5000,
        ]);

        $response = $this->postJson(route('subscriptions.store'), [
            'plan_id' => $plan->id
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'subscription_id', 'payment_url']);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'locked_price' => 5000,
        ]);
    }

    public function testUserCannotSubscribeIfAlreadyHasActiveSubscription()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
        $plan = Plan::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'locked_price' => $plan->price,
        ]);

        $response = $this->postJson(route('subscriptions.store'), [
            'plan_id' => $plan->id
        ]);

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Você já possui uma assinatura ativa.']);
    }

    public function testUserCannotSubscribeToInactivePlan()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $Plan = Plan::factory()->create(['status' => 'inactive']);

        $response = $this->postJson(route('subscriptions.store'), [
            'plan_id' => $Plan->id
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Este plano não está disponível.']);
    }

    public function testExistingPendingSubscriptionReturnsSameLink()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
        $plan = Plan::factory()->create(['status' => 'active']);

        $firstSub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'payment_url' => 'https://link-original.com',
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('subscriptions.store'), [
            'plan_id' => $plan->id
        ]);

        $response->assertStatus(200);
        
        $response->assertJson([
            'message' => 'Você já possui uma assinatura pendente para este plano. Prossiga para o pagamento.',
            'subscription_id' => $firstSub->id,
            'payment_url' => 'https://link-original.com',
        ]);

        $this->assertDatabaseCount('subscriptions', 1);
    }
}
