<?php

namespace App\Listeners;

use App\Models\Subscription;
use App\Services\SubscriptionService;

class SyncPaddleSubscription
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function handle(object $event): void
    {
        if (! $event->subscription instanceof Subscription) {
            return;
        }

        $priceId = $event->subscription->items->first()?->price_id;
        $plan = collect(config('plans'))
            ->search(fn (array $plan): bool => filled($plan['paddle_price_id'])
                && hash_equals((string) $plan['paddle_price_id'], (string) $priceId));

        if ($plan === false) {
            return;
        }

        $event->subscription->forceFill([
            'user_id' => $event->billable->getKey(),
            'provider' => 'paddle',
            'provider_id' => $event->subscription->paddle_id,
            'plan' => $plan,
        ])->save();

        $this->subscriptions->recordProviderEvent(
            $event->subscription,
            $event->payload['event_type'] ?? 'subscription.updated',
            $event->payload['event_id'] ?? null,
        );
    }
}
