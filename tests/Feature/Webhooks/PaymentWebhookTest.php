<?php

namespace Tests\Feature\Webhooks;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionActiveNotification;
use App\Notifications\SubscriptionPaymentFailedNotification;
use App\Notifications\SubscriptionPlanChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function testWebhookConfirmsPaymentAndActivatesSubscription()
    {
        Notification::fake();

        $subscription = Subscription::factory()->create([
            'gateway_id' => 'tx_123456',
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('webhooks.payment'), [
            'event' => 'invoice.paid',
            'gateway_id' => 'tx_123456',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
        ]);

        $subscription->refresh();
        $this->assertNotNull($subscription->started_at);
        $this->assertNotNull($subscription->ends_at);

        Notification::assertSentTo($subscription->user, SubscriptionActiveNotification::class);
    }

    public function testWebhookNotifiesUserOnPaymentFailure()
    {
        Notification::fake();

        $subscription = Subscription::factory()->create([
            'status' => 'pending',
            'gateway_id' => 'tx_fail_123',
        ]);

        $payload = [
            'event' => 'invoice.payment_failed',
            'gateway_id' => 'tx_fail_123',
        ];

        $response = $this->postJson(route('webhooks.payment'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'pending', 
        ]);

        Notification::assertSentTo($subscription->user, SubscriptionPaymentFailedNotification::class);
    }

    public function testWebhookActivatesNewPlanAndCancelsOldOne()
    {
        Notification::fake();
        $user = User::factory()->create();

        $oldSub = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'created_at' => now()->subMonths(2),
        ]);

        $newSub = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'gateway_id' => 'tx_swap_999',
        ]);

        $this->postJson(route('webhooks.payment'), [
            'event' => 'invoice.paid',
            'gateway_id' => 'tx_swap_999',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $oldSub->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $newSub->id,
            'status' => 'active',
        ]);

        Notification::assertSentTo($user, SubscriptionPlanChangedNotification::class);
    }
}
