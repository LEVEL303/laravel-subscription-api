<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function testEmailCanBeVerified()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'status' => 'inactive',
        ]);

        Event::fake();

        $verificationURL = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->getJson($verificationURL);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'E-mail verificado com sucesso!']);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertEquals('active', $user->fresh()->status);

        Event::assertDispatched(Verified::class);
    }

    public function testEmailIsNotVerifiedWithInvalidHash()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'status' => 'inactive',
        ]);

        $verificationURL = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );
        
        $tamperedURL = str_replace('signature=', 'signature=fake', $verificationURL);
        
        $response = $this->getJson($tamperedURL);

        $response->assertStatus(403);
        
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertEquals('inactive', $user->fresh()->status);
    }
}
