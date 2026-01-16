<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function testItExpiresEligibleSubscriptions()
    {
        Notification::fake();

        $subToExpire = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subDay(),
            'auto_renew' => false,
        ]);

        $subToRenew = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subDay(),
            'auto_renew' => true,
        ]);

        $subFuture = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDay(),
            'auto_renew' => false,
        ]);

        Artisan::call('subscriptions:expire');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subToExpire->id,
            'status' => 'expired',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subToRenew->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subFuture->id,
            'status' => 'active',
        ]);

        Notification::assertSentTo($subToExpire->user, SubscriptionExpiredNotification::class);
    }
}
