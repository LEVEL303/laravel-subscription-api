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
        $currentPlan = Plan::factory()->create(['price' => 5000]);
        $currentSub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
            'status' => 'active',
            'locked_price' => $currentPlan->price,
        ]);
        $newPlan = Plan::factory()->create(['price' => 10000]);

        Sanctum::actingAs($user, ['*']);

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

    public function testCannotSwapToSamePlan()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson(route('subscriptions.swap'), [
            'plan_id' => $plan->id
        ]);

        $response->assertStatus(422);
    }
}
