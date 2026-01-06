<?php

namespace Tests\Feature\Plans;

use App\Models\User;
use App\Models\Plan;
use App\Models\Feature;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsAdmin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);
        return $admin;
    }

    public function testAdminCanAttachFeature()
    {
        $this->signInAsAdmin();

        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create();

        $response = $this->postJson(route('plans.features.store', $plan->id), [
            'feature_id' => $feature->id
        ]);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Funcionalidade vinculada com sucesso!']);

        $this->assertDatabaseHas('feature_plan', [
            'plan_id' => $plan->id,
            'feature_id' => $feature->id
        ]);
    }

    public function testAdminCanDetachFeature()
    {
        $this->signInAsAdmin();

        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create();
        
        $plan->features()->attach($feature);

        $response = $this->deleteJson(route('plans.features.destroy', [$plan->id, $feature->id]));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Funcionalidade desvinculada com sucesso!']);

        $this->assertDatabaseMissing('feature_plan', [
            'plan_id' => $plan->id,
            'feature_id' => $feature->id
        ]);
    }

    public function testRegularUserCannotAttachFeature()
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user, ['*']);

        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create();

        $response = $this->postJson(route('plans.features.store', $plan->id), [
            'feature_id' => $feature->id
        ]);

        $response->assertStatus(403);
    }

    public function testRegularUserCannotDetachFeature()
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user, ['*']);
        
        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create();
        $plan->features()->attach($feature);

        $response = $this->deleteJson(route('plans.features.destroy', [$plan->id, $feature->id]));

        $response->assertStatus(403);
    }
}
