<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanResetPasswordWithValidToken()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->postJson(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Sua senha foi redefinida com sucesso!',
        ]);

        $this->assertTrue(Hash::check('NovaSenha123', $user->fresh()->password));
    }

    public function testResetFailWithInvalidToken()
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('password.update'), [
            'token' => 'token-invalido',
            'email' => $user->email,
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ]);

        $response->assertStatus(400);
    }
}
