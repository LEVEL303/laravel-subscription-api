<?php

namespace Tests\Feature\Plans;

use App\Models\Plan;
use App\Models\Feature;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeatureAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/test-vip-route', function () {
            return 'Acesso Permitido';
        })->middleware(['auth:sanctum', 'check.feature:vip_reports']);
    }

    public function testUserWithCorrectPlanFeatureCanAccessRoute()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create(['code' => 'vip_reports']);

        $plan->features()->attach($feature);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/test-vip-route');

        $response->assertStatus(200);
        $response->assertSee('Acesso Permitido');
    }

    public function testUserWithoutFeatureCannotAccessRoute()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/test-vip-route');

        $response->assertStatus(403);
    }

    public function testUserWithExpiredSubscriptionCannotAccessRoute()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create(['code' => 'vip_reports']);

        $plan->features()->attach($feature);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'cancelled',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/test-vip-route');

        $response->assertStatus(403);
    }
}
