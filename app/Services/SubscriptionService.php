<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function startTrial(User $user, string $plan): Subscription
    {
        return DB::transaction(function () use ($user, $plan) {
            $subscription = $user->subscription()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan' => $plan,
                    'status' => 'trialing',
                    'trial_ends_at' => now()->addDays(14),
                    'renews_at' => null,
                    'ends_at' => null,
                ],
            );

            $this->record($subscription, 'trial_started', [
                'plan' => $plan,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            ]);

            return $subscription;
        });
    }

    public function changePlan(Subscription $subscription, string $plan): Subscription
    {
        return DB::transaction(function () use ($subscription, $plan) {
            $previousPlan = $subscription->plan;
            $subscription->update([
                'plan' => $plan,
                'status' => $subscription->status === 'canceled' ? 'trialing' : $subscription->status,
                'trial_ends_at' => $subscription->trial_ends_at ?? now()->addDays(14),
                'ends_at' => null,
            ]);

            $this->record($subscription, 'plan_changed', [
                'from' => $previousPlan,
                'to' => $plan,
            ]);

            return $subscription->refresh();
        });
    }

    public function cancel(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status' => 'canceled',
                'ends_at' => now(),
                'renews_at' => null,
            ]);

            $this->record($subscription, 'canceled');

            return $subscription->refresh();
        });
    }

    public function expireTrial(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status' => 'expired',
                'ends_at' => now(),
            ]);

            $this->record($subscription, 'trial_expired');

            return $subscription->refresh();
        });
    }

    public function record(Subscription $subscription, string $type, array $payload = []): void
    {
        $subscription->events()->create([
            'user_id' => $subscription->user_id,
            'type' => $type,
            'payload' => $payload ?: null,
            'occurred_at' => now(),
        ]);
    }
}
