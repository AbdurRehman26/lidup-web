<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPackage extends Model
{
    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function hasCapacity(): bool
    {
        return $this->user_limit === null || $this->users_count < $this->user_limit;
    }

    public function endsAt()
    {
        return match ($this->duration_unit) {
            'days' => now()->addDays($this->duration_value),
            'months' => now()->addMonthsNoOverflow($this->duration_value),
            default => null,
        };
    }

    public function durationLabel(): string
    {
        return match ($this->duration_unit) {
            'unlimited' => 'Unlimited',
            'months' => $this->duration_value.' '.str('month')->plural($this->duration_value),
            default => $this->duration_value.' '.str('day')->plural($this->duration_value),
        };
    }

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'price' => 'decimal:2',
            'device_limit' => 'integer',
            'user_limit' => 'integer',
            'duration_value' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
