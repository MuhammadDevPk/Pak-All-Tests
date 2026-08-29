<?php

namespace App\Console\Commands;

use App\Services\SubscriptionManager;
use Illuminate\Console\Command;

class ExpireLapsedSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:expire-lapsed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batch-expire all active user subscriptions that have passed their expiration timestamp';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionManager $subscriptionManager): int
    {
        $this->info('Scanning for lapsed subscriptions...');

        $expiredCount = $subscriptionManager->expireLapsedSubscriptions();

        if ($expiredCount > 0) {
            $this->info("Successfully expired {$expiredCount} lapsed subscription(s).");
        } else {
            $this->info('No lapsed subscriptions found. All active subscriptions are within valid access windows.');
        }

        return self::SUCCESS;
    }
}
