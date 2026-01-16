<?php

namespace Tests\Feature\Subscriptions;

use App\Models\User;
use App\Models\Plan;
use App\Models\Feature;
use App\Models\Subscription;
use App\Notifications\SubscriptionCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/test-access', function () {
            return 'Acesso Permitido';
        })->middleware(['auth:sanctum', 'check.feature:access_system']);
    }

    public function testUserCanCancelSubscription()
    {
        Notification::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'gateway_id' => 'sub_123',
            'status' => 'active',
            'ends_at' => now()->addDays(10),
            'auto_renew' => true,
        ]);

        $response = $this->deleteJson(route('subscriptions.cancel'));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Renovação automática cancelada. Seu acesso continua até o fim do período.']);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
            'auto_renew' => false,
        ]);

        $subscription->refresh();
        $this->assertTrue($subscription->ends_at->greaterThan(now()));

        Notification::assertSentTo($user, SubscriptionCancelledNotification::class);
    }

    public function testUserStillHasAccessAfterCancellationUntilExpiration()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create(['code' => 'access_system']);
        $plan->features()->attach($feature);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->addDay(),
            'auto_renew' => false,
        ]);

        $response = $this->getJson('/test-access');
        
        $response->assertStatus(200);
        $response->assertSee('Acesso Permitido');
    }

    public function testUserLosesAccessAfterExpiration()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create(['code' => 'access_system']);
        $plan->features()->attach($feature);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->subDay(),
            'auto_renew' => false,
        ]);

        $response = $this->getJson('/test-access');
        
        $response->assertStatus(403);
    }
}
