<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class PrunePendingSubscriptions extends Command
{
    protected $signature = 'subscriptions:prune-pending';

    protected $description = 'Remove pending subscriptions created more than 48 hours ago.';

    public function handle()
    {
        $cutOffDate = now()->subHours(48);

        $deletedCount = Subscription::where('status', 'pending')
            ->where('created_at', '<', $cutOffDate)
            ->delete();

        if ($deletedCount > 0) {
            $this->info("Success: {$deletedCount} old pending subscriptions have been removed.");
        } else {
            $this->info('No old pending subscriptions were found for removal');
        }
    }
}
