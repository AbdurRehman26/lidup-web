<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Paddle\Billable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable;

    public function appSubscription(): MorphOne
    {
        return $this->morphOne(Subscription::class, 'billable')->latestOfMany();
    }

    public function subscriptionPackage(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function subscriptionEvents(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function appActivations(): HasMany
    {
        return $this->hasMany(AppActivation::class);
    }

    public function taskCompletionEvents(): HasMany
    {
        return $this->hasMany(TaskCompletionEvent::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function generateTokenString(): string
    {
        $entropy = Str::random((int) config('sanctum.token_entropy_length', 24));

        return config('sanctum.token_prefix', '').$entropy.hash('crc32b', $entropy);
    }

    public function onAppTrial(): bool
    {
        if (in_array($this->subscriptionPackage?->duration_unit, ['unlimited', 'lifetime'], true)) {
            return true;
        }

        return $this->trial_started_at !== null
            && ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }

    public function hasAppEntitlement(): bool
    {
        return $this->appSubscription?->isEntitled() === true || $this->onAppTrial();
    }

    public function entitlementPlan(): ?string
    {
        return $this->appSubscription?->isEntitled() === true
            ? $this->appSubscription->plan
            : ($this->onAppTrial() ? ($this->trial_plan ?? $this->subscriptionPackage?->plan) : null);
    }

    public function entitlementStatus(): string
    {
        if ($this->subscriptionPackage?->duration_unit === 'lifetime') {
            return 'active';
        }

        if ($this->appSubscription?->isEntitled() === true) {
            return $this->appSubscription->status;
        }

        if ($this->onAppTrial()) {
            return 'trialing';
        }

        return $this->trial_started_at !== null && $this->trial_ends_at?->isPast() === true
            ? 'trial_expired'
            : 'inactive';
    }

    public function entitlementEndsAt(): mixed
    {
        if ($this->appSubscription?->isEntitled() === true) {
            return $this->appSubscription->ends_at ?? $this->appSubscription->trial_ends_at;
        }

        return $this->trial_ends_at;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
