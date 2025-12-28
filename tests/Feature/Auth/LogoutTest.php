<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthenticatedUserCanLogout()
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson(route('logout'));
    
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Sessão encerrada com sucesso!']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function testUnauthenticatedUserCannotLogout()
    {
        $response = $this->postJson(route('logout'));

        $response->assertStatus(401);
    }
}
