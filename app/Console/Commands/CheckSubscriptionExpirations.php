<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiredNotification;
use Illuminate\Console\Command;

class CheckSubscriptionExpirations extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Verifica assinaturas ativas que venceram e altera status para expired';

    public function handle()
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->where('auto_renew', false)
            ->get();

        foreach ($expiredSubscriptions as $sub) {
            $sub->update(['status' => 'expired']);

            try {
                $sub->user->notify(new SubscriptionExpiredNotification());
            } catch (\Exception $e) {
                $this->error("Failed to notify user {$sub->user->id}: {$e->getMessage()}");
            }

            $this->info("Subscription {$sub->id} expired.");
        }
    }
}
