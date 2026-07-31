<?php

namespace App\Services;

use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrialService
{
    public function assignIfEligible(User $user, string $requestedPlan): bool
    {
        if ($user->trial_started_at) {
            return false;
        }

        return DB::transaction(function () use ($user, $requestedPlan): bool {
            $package = SubscriptionPackage::query()
                ->active()
                ->where('is_paid', false)
                ->withCount('users')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->first(fn (SubscriptionPackage $candidate): bool => $candidate->hasCapacity());

            if (! $package) {
                return false;
            }

            $position = (int) User::query()
                ->whereNotNull('trial_cohort_position')
                ->lockForUpdate()
                ->max('trial_cohort_position') + 1;

            $user->forceFill([
                'trial_cohort_position' => $position,
                'subscription_package_id' => $package->id,
                'trial_plan' => array_key_exists($requestedPlan, config('plans')) ? $requestedPlan : $package->plan,
                'trial_started_at' => now(),
                'trial_ends_at' => $package->endsAt(),
            ])->save();

            return true;
        }, 3);
    }

    public function currentOffer(): ?SubscriptionPackage
    {
        return SubscriptionPackage::query()
            ->active()
            ->where('is_paid', false)
            ->withCount('users')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->first(fn (SubscriptionPackage $package): bool => $package->hasCapacity());
    }

    public function publicPackages()
    {
        return SubscriptionPackage::query()
            ->active()
            ->visible()
            ->withCount('users')
            ->orderBy('sort_order')
            ->get();
    }

    public function present(?SubscriptionPackage $package): ?array
    {
        if (! $package) {
            return null;
        }

        $usersCount = $package->users_count ?? $package->users()->count();

        return [
            'id' => $package->id,
            'name' => $package->name,
            'slug' => $package->slug,
            'description' => $package->description,
            'plan' => $package->plan,
            'device_limit' => $package->device_limit,
            'user_limit' => $package->user_limit,
            'users_count' => $usersCount,
            'remaining_spots' => $package->user_limit === null
                ? null
                : max(0, $package->user_limit - $usersCount),
            'duration_label' => $package->durationLabel(),
            'duration_unit' => $package->duration_unit,
            'duration_value' => $package->duration_value,
            'is_paid' => $package->is_paid,
            'price' => $package->price,
            'currency' => $package->currency,
        ];
    }
}
