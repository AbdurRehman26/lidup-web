<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Subscription', [
            'subscription' => $request->user()->subscription,
            'plans' => config('plans'),
        ]);
    }

    public function update(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('plans')))],
        ]);

        $subscription = $request->user()->subscription;

        if ($subscription) {
            $subscriptions->changePlan($subscription, $validated['plan']);
        } else {
            $subscriptions->startTrial($request->user(), $validated['plan']);
        }

        return back()->with('subscription_updated', 'Your plan has been updated.');
    }

    public function cancel(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        if ($request->user()->subscription) {
            $subscriptions->cancel($request->user()->subscription);
        }

        return back()->with('subscription_updated', 'Your subscription has been canceled.');
    }
}
