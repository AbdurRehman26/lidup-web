<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Paddle\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription): void {
            $subscription->user_id ??= $subscription->billable_id;
            $subscription->type ??= self::DEFAULT_TYPE;
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function isEntitled(): bool
    {
        return filled($this->paddle_id) && $this->valid();
    }
}
