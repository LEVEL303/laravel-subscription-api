<?php

namespace Tests\Feature\Plans;

use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
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

    public function testAdminCanListAllPlansWithAllDetails()
    {
        $this->signInAsAdmin();

        Plan::factory()->create([
            'name' => 'Plano Ativo',
            'status' => 'active',
        ]);

        Plan::factory()->create([
            'name' => 'Plano Inativo',
            'status' => 'inactive',
        ]);

        $response = $this->getJson(route('plans.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Plano Inativo']);

        $data = $response->json()[0];
        $this->assertArrayHasKey('status', $data);
    }

    public function testAdminCanCreatePlan()
    {
        $this->signInAsAdmin();

        $response = $this->postJson(route('plans.store'), [
            'name' => 'Plano Pro',
            'description' => 'Acesso total',
            'price' => '1990',
            'trial_days' => 14,
            'period' => 'monthly',
            'status' => 'active', 
        ]); 

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Plano cadastrado com sucesso!']);

        $this->assertDatabaseHas('plans', [
            'name' => 'Plano Pro',
            'description' => 'Acesso total',
            'slug' => 'plano-pro',
            'price' => '1990',
            'trial_days' => 14,
            'period' => 'monthly',
            'status' => 'active', 
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

    public function testAdminCanUpdatePlan()
    {
        $this->signInAsAdmin();

        $plan = Plan::factory()->create([
            'name' => 'Plano Antigo',
            'price' => 2000,
            'trial_days' => 0,
            'period' => 'monthly',
            'status' => 'inactive',
        ]);

        $response = $this->putJson(route('plans.update', $plan->id), [
            'name' => 'Plano Novo',
            'description' => 'Descrição atualizada',
            'price' => 3000,
            'trial_days' => 14,
            'period' => 'yearly',
            'status' => 'active',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Plano atualizado com sucesso!']);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Plano Novo',
            'slug' => 'plano-novo',
            'description' => 'Descrição atualizada',
            'price' => 3000,
            'trial_days' => 14,
            'period' => 'yearly',
            'status' => 'active',
        ]);
    }

    public function testPlanUpdateValidatesUniqueField()
    {
        $this->signInAsAdmin();

        Plan::factory()->create(['name' => 'Plano Existente']);
        $otherPlan = Plan::factory()->create(['name' => 'Outro Plano']);

        $response = $this->putJson(route('plans.update', $otherPlan->id), [
            'name' => 'Plano Existente',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function testRegularUserCannotUpdatePlan()
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user, ['*']);

        $plan = Plan::factory()->create([
            'name' => 'Plano Pro',
            'price' => 3000,
            'status' => 'active',
        ]);

        $response = $this->putJson(route('plans.update', $plan->id), [
            'name' => 'Plano Alterado',
            'price' => 0,
            'status' => 'inactive',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Plano Pro',
            'price' => 3000,
            'status' => 'active',
        ]);
    }

    public function testAdminCanDeletePlanWithNoSubscriptions()
    {
        $this->signInAsAdmin();

        $plan = Plan::factory()->create();

        $response = $this->deleteJson(route('plans.destroy', $plan->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Plano excluído com sucesso!']);

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function testPlasIsInactivatedInsteadOfDeletedIfHasSubscriptions()
    {
        $this->signInAsAdmin();

        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'locked_price' => 2000,
            'status' => 'active',
        ]);

        $response = $this->deleteJson(route('plans.destroy', $plan->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Plano inativado pois possui assinaturas vinculadas.']);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => 'inactive',
        ]);
    }

    public function testRegularUserCannotDeletePlan()
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user, ['*']);

        $plan = Plan::factory()->create();

        $response = $this->deleteJson(route('plans.destroy', $plan->id));

        $response->assertStatus(403);

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
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

    public function testAdminCanViewInactivePlan()
    {
        $this->signInAsAdmin();
        $plan = Plan::factory()->create(['status' => 'inactive']);

        $response = $this->getJson(route('plans.show', $plan));

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'inactive']);
    }
}
