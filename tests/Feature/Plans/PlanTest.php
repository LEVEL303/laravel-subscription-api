<?php

namespace Tests\Feature\Plans;

use App\Models\Plan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsAdmin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);
        return $admin;
    }

    public function testAdminCanCreateAPlan()
    {
        $this->signInAsAdmin();

        $response = $this->postJson(route('plans.store'), [
            'name' => 'Plano Pro',
            'description' => 'Acesso total',
            'price' => '1990',
            'period' => 'monthly',
            'status' => 'active', 
        ]); 

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Plano cadastrado com sucesso!']);

        $this->assertDatabaseHas('plans', [
            'slug' => 'plano-pro',
            'price' => '1990',
        ]);
    }

    public function testPlanCreationValidatesUniqueFiled()
    {
        $this->signInAsAdmin();

        Plan::factory()->create(['name' => 'Plano Existente']);

        $response = $this->postJson(route('plans.store'), [
            'name' => 'Plano Existente',
            'description' => 'Teste de campo name',
            'price' => '1990',
            'period' => 'monthly',
            'status' => 'active', 
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function testRegularUserCannotCreatePlan()
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson(route('plans.store'), [
            'name' => 'Hacker Plan',
            'description' => 'Tentativa',
            'price' => 100,
            'period' => 'monthly',
            'status' => 'active',
        ]);

        $response->assertStatus(403);
    }
}
