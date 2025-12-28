<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanRequestPasswordResetLink()
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Se houver um cadastro com o e-mail informado, um link de redefinição de senha será enviado.'
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function testUserReceivesGenericMessageEvenIfEmailDoesntExist()
    {
        $response = $this->postJson(route('password.email'), [
            'email' => 'fantasma@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Se houver um cadastro com o e-mail informado, um link de redefinição de senha será enviado.'
        ]);
    }
}
