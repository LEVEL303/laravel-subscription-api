<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PrunePendingSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function testItDeletesOldPendingSubscriptions()
    {
        $oldPendingSub = Subscription::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(49),
        ]);

        $recentPendingSub = Subscription::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(2),
        ]);

        $oldActiveSub = Subscription::factory()->create([
            'status' => 'active',
            'created_at' => now()->subHours(100),
        ]);

        Artisan::call('subscriptions:prune-pending');

        $this->assertDatabaseMissing('subscriptions', ['id' => $oldPendingSub->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $recentPendingSub->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $oldActiveSub->id]);
    }
}
