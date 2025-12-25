<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanRegisterSuccessfully()
    {
        Notification::fake();

        $payload = [
            'name' => 'Teste User',
            'email' => 'teste@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ];

        $response = $this->postJson(route('register.store'), $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => "teste@example.com",
            'status' => 'inactive',
        ]);

        $user = User::where('email', 'teste@example.com')->first();
        $this->assertNotEquals('senha123', $user->password);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testRegistrationValidatesRequiredFields()
    {
        $response = $this->postJson(route('register.store'), []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function testRegistrationValidatesEmailUniqueness()
    {
        User::factory()->create(['email' => 'duplicado@example.com']);

        $response = $this->postJson(route('register.store'), [
            'name' => 'Outro',
            'email' => 'duplicado@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['email']);
    }

    public function testPasswordConfirmationMustMatchPassword() 
    {
        $response = $this->postJson(route('register.store'), [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'diferente',
        ]);
        
        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['password']);
    }

    public function testPasswordMustMeetComplexityRequirements()
    {
        $this->postJson(route('register.store'), [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => 'scurta7',
            'password_confirmation' => 'scurta7',
        ])->assertJsonValidationErrors(['password']);

        $this->postJson(route('register.store'), [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => 'apenasLetras',
            'password_confirmation' => 'apenasLetras',
        ])->assertJsonValidationErrors(['password']);

        $this->postJson(route('register.store'), [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertJsonValidationErrors(['password']);
    }
}
