<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function show(Request $request): Response
    {
        $plans = $this->paidPlans();

        return Inertia::render('Subscription', [
            'subscription' => $request->user()->appSubscription,
            'plans' => $plans,
            'billingConfigured' => filled(config('cashier.client_side_token'))
                && collect($plans)->every(fn (array $plan): bool => filled($plan['paddle_price_id'])),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $plans = $this->paidPlans();
        $validated = $request->validate(['plan' => ['required', Rule::in(array_keys($plans))]]);
        $priceId = $plans[$validated['plan']]['paddle_price_id'];

        if (! $priceId || ! config('cashier.client_side_token')) {
            throw ValidationException::withMessages([
                'plan' => 'Paddle billing is not configured yet. Add the client token and plan price IDs.',
            ]);
        }

        $subscription = $request->user()->subscription('default');

        if ($subscription?->paddle_id && $subscription->valid()) {
            throw ValidationException::withMessages([
                'plan' => 'You already have a subscription. Change your existing plan instead.',
            ]);
        }

        $checkout = $request->user()
            ->subscribe($priceId, 'default')
            ->returnTo(route('dashboard', ['checkout' => 'success']));

        return response()->json(['checkout' => $checkout->options()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $plans = $this->paidPlans();
        $validated = $request->validate(['plan' => ['required', Rule::in(array_keys($plans))]]);
        $subscription = $request->user()->subscription('default');
        $priceId = $plans[$validated['plan']]['paddle_price_id'];

        if (! $subscription || ! $subscription->paddle_id || ! $subscription->valid()) {
            return back()->withErrors(['plan' => 'Start a Paddle subscription before changing plans.']);
        }

        if (! $priceId) {
            return back()->withErrors(['plan' => 'This Paddle price has not been configured.']);
        }

        if (! $subscription->hasPrice($priceId)) {
            $subscription->swap($priceId);
        }

        return back()->with('subscription_updated', 'Your Paddle subscription has been updated.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $request->user()->subscription('default');

        if ($subscription?->paddle_id && ! $subscription->canceled()) {
            $subscription->cancel();
        }

        return back()->with('subscription_updated', 'Your subscription will end after the current billing period.');
    }

    private function paidPlans(): array
    {
        $packages = SubscriptionPackage::query()
            ->active()
            ->visible()
            ->where('is_paid', true)
            ->orderBy('sort_order')
            ->get();

        if ($packages->isEmpty()) {
            return config('plans');
        }

        return $packages->mapWithKeys(fn (SubscriptionPackage $package): array => [
            $package->slug => [
                'name' => $package->name,
                'price' => $package->price,
                'interval' => 'month',
                'devices' => $package->device_limit,
                'paddle_price_id' => $package->paddle_price_id,
                'plan' => $package->plan,
            ],
        ])->all();
    }
}
