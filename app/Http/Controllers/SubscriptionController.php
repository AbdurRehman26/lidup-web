<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionPackageService $packages) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $plans = $this->packages->paidPlans();

        if ($plans === []) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Subscription', [
            'subscription' => $request->user()->appSubscription,
            'plans' => $plans,
            'billingConfigured' => filled(config('cashier.client_side_token')),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $plans = $this->packages->paidPlans();

        if ($plans === []) {
            return response()->json([
                'message' => 'Paid packages are not available during early access.',
                'errors' => ['plan' => ['Paid packages are not available during early access.']],
            ], 422);
        }

        $validated = $request->validate(['plan' => ['required', Rule::in(array_keys($plans))]]);
        $priceId = $plans[$validated['plan']]['paddle_price_id'];

        if (! $priceId || ! config('cashier.client_side_token')) {
            throw ValidationException::withMessages([
                'plan' => 'Paddle billing is not configured yet. Add the client token and plan price IDs.',
            ]);
        }

        $checkout = $request->user()
            ->checkout($priceId)
            ->customData(['package_slug' => $validated['plan']])
            ->returnTo(route('dashboard', ['checkout' => 'success']));

        return response()->json(['checkout' => $checkout->options()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $plans = $this->packages->paidPlans();
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
}
