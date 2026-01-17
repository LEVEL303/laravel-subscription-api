<?php

namespace Tests\Feature\Subscriptions;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionSwapTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanRequestPlanSwap()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $currentPlan = Plan::factory()->create(['price' => 5000]);
        $currentSub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
            'status' => 'active',
            'locked_price' => $currentPlan->price,
        ]);
        $newPlan = Plan::factory()->create(['price' => 10000]);

        $response = $this->postJson(route('subscriptions.swap'), [
            'plan_id' => $newPlan->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'payment_url']);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'status' => 'pending',
            'locked_price' => 10000,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $currentSub->id,
            'status' => 'active',
        ]);
    }

    public function testUserCannotSwapToSamePlan()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        Plan::factory()->create();
        
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->postJson(route('subscriptions.swap'), [
            'plan_id' => $plan->id
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Você já está neste plano.']);
    }

    public function testUserCannotSwapToInactivePlan()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $currentPlan = Plan::factory()->create(['status' => 'active']);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
            'status' => 'active'
        ]);

        $inactivePlan = Plan::factory()->create(['status' => 'inactive']);

        $response = $this->postJson(route('subscriptions.swap'), [
            'plan_id' => $inactivePlan->id
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Este plano não está disponível.']);
    }

    public function testExistingPendingSwapReturnsSameLink()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $planA = Plan::factory()->create(['price' => 5000]);
        $planB = Plan::factory()->create(['price' => 9000]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $planA->id,
            'status' => 'active'
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $planB->id,
            'gateway_id' => 'tx_old_123',
            'payment_url' => 'https://swap-link-original.com',
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('subscriptions.swap'), [
            'plan_id' => $planB->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Você já possui uma assinatura pendente para este plano. Prossiga para o pagamento.',
            'payment_url' => 'https://swap-link-original.com',
        ]);

        $this->assertDatabaseCount('subscriptions', 2);
    }
}
