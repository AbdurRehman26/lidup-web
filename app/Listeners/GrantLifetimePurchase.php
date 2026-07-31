<?php

namespace App\Listeners;

use App\Models\SubscriptionPackage;
use App\Models\User;
use Laravel\Paddle\Events\TransactionCompleted;

class GrantLifetimePurchase
{
    public function handle(TransactionCompleted $event): void
    {
        if (! $event->billable instanceof User) {
            return;
        }

        $priceIds = collect(data_get($event->payload, 'data.items', []))
            ->pluck('price.id')
            ->filter();

        $package = SubscriptionPackage::query()
            ->where('is_paid', true)
            ->where('billing_interval', 'one_time')
            ->whereIn('paddle_price_id', $priceIds)
            ->first();

        if (! $package) {
            return;
        }

        $event->billable->forceFill([
            'subscription_package_id' => $package->id,
            'trial_plan' => $package->plan,
            'trial_started_at' => $event->billable->trial_started_at ?? now(),
            'trial_ends_at' => null,
        ])->save();
    }
}
