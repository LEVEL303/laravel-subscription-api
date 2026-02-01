<?php

namespace Tests\Feature\Plans;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function testRegularUserCanListActivePlans()
    {
        Plan::factory()->create([
            'name' => 'Plano Ativo',
            'status' => 'active',
        ]);

        Plan::factory()->create([
            'name' => 'Plano Inativo',
            'status' => 'Inactive',
        ]);

        $response = $this->getJson(route('plans.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Plano Ativo']);
        $response->assertJsonMissing(['name' => 'Plano Inativo']);

        $data = $response->json()[0];
        $this->assertArrayNotHasKey('status', $data);
    }

    public function testRegularUserCanViewActivePlanDetails()
    {
        $plan = Plan::factory()->create([
            'name' => 'Plano Pro',
            'slug' => 'plano-pro',
            'status' => 'active',
        ]);

        $response = $this->getJson(route('plans.show', $plan));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Plano Pro']);

        $data = $response->json();
        $this->assertArrayNotHasKey('status', $data);
    }

    public function testRegularUserCannotViewInactivePlan()
    {
        $plan = Plan::factory()->create(['status' => 'inactive']);

        $response = $this->getJson(route('plans.show', $plan));

        $response->assertStatus(404);
    }
}