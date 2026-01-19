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

        $payload = [
            'event' => 'invoice.paid',
            'gateway_id' => 'tx_123456',
            'webhook_id' => 'evt_123456',
        ];

        $response = $this->postJson(route('webhooks.payment'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('webhook_logs', [
            'gateway_event_id' => 'evt_123456',
            'status' => 'processed',
        ]);

        $subscription->refresh();
        $this->assertNotNull($subscription->started_at);
        $this->assertNotNull($subscription->ends_at);

        Notification::assertSentTo($subscription->user, SubscriptionActiveNotification::class);
    }

    public function testWebhookSuspendsSubscriptionOnRecurringPaymentFailure()
    {
        Notification::fake();

        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'gateway_id' => 'tx_fail_123',
        ]);

        $payload = [
            'event' => 'invoice.payment_failed',
            'gateway_id' => 'tx_fail_123',
            'webhook_id' => 'evt_123',
        ];

        $response = $this->postJson(route('webhooks.payment'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'inactive', 
        ]);

        $this->assertDatabaseHas('webhook_logs', [
            'gateway_event_id' => 'evt_123',
            'status' => 'processed'
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

        $payload = [
            'event' => 'invoice.paid',
            'gateway_id' => 'tx_swap_999',
            'webhook_id' => 'evt_999'
        ];

        $this->postJson(route('webhooks.payment'), $payload);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $oldSub->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $newSub->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('webhook_logs', [
            'gateway_event_id' => 'evt_999',
            'status' => 'processed'
        ]);

        Notification::assertSentTo($user, SubscriptionPlanChangedNotification::class);
    }

    public function testWebhookIsIdempotentIgnoresDuplicateEvents()
    {
        $subscription = Subscription::factory()->create([
            'gateway_id' => 'tx_duplicate',
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        $payload = [
            'event' => 'invoice.paid',
            'gateway_id' => 'tx_duplicate',
            'webhook_id' => 'evt_duplicate_unique_id',
        ];

        $this->postJson(route('webhooks.payment'), $payload)->assertStatus(200);

        $subscription->refresh();
        $firstEndsAt = $subscription->ends_at;

        $response = $this->postJson(route('webhooks.payment'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Event already processed']);

        $subscription->refresh();
        $this->assertEquals($firstEndsAt, $subscription->ends_at);
        
        $this->assertDatabaseCount('webhook_logs', 1);
    }
}

