<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptionTrials extends Command
{
    protected $signature = 'subscriptions:expire-trials';

    protected $description = 'Expire subscription trials that have passed their end date';

    public function handle(SubscriptionService $subscriptions): int
    {
        $expired = 0;

        Subscription::query()
            ->where('status', 'trialing')
            ->where('trial_ends_at', '<=', now())
            ->eachById(function (Subscription $subscription) use ($subscriptions, &$expired) {
                $subscriptions->expireTrial($subscription);
                $expired++;
            });

        $this->info("Expired {$expired} subscription trial(s).");

        return self::SUCCESS;
    }
}
