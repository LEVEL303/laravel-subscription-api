<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
   use RefreshDatabase;

   public function testUserCanLoginWithValidCredentials()
   {
        $user = User::factory()->create([
            'password' => 'senha123',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'senha123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token', 'token_type']);
   }

   public function testLoginFailsWithInvalidCredentials()
   {
        $user = User::factory()->create([
            'password' => 'senha123',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'senhaErrada',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Credenciais inválidas.']);
   }

   public function testUserCannotLoginIfInactive()
   {
        $user = User::factory()->create([
            'password' => 'senha123',
            'status' => 'inactive',
        ]);

        $response = $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'senha123',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Sua conta não está ativa.']);
   }
}
