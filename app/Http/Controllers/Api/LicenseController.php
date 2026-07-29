<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token || ! $token->can('activation:verify')) {
            return response()->json([
                'valid' => false,
                'reason' => 'ability_denied',
                'message' => 'This key cannot validate a LidUp license.',
            ], 403);
        }

        $user = $request->user();
        $subscription = $user->appSubscription;
        $valid = $user->hasAppEntitlement();
        $plan = $user->entitlementPlan();
        $deviceLimit = $user->onAppTrial() && $user->subscriptionPackage
            ? $user->subscriptionPackage->device_limit
            : ($plan ? (int) data_get(config('plans'), "{$plan}.devices", 0) : 0);

        return response()->json([
            'valid' => $valid,
            'reason' => $valid ? null : 'subscription_inactive',
            'plan' => $plan,
            'subscription_status' => $user->entitlementStatus(),
            'entitlement_source' => $subscription?->isEntitled() === true ? 'subscription' : ($user->onAppTrial() ? 'trial' : null),
            'entitled_until' => $user->entitlementEndsAt()?->toIso8601String(),
            'device_limit' => $deviceLimit,
            'active_devices' => $user->appActivations()->active()->count(),
        ], $valid ? 200 : 403);
    }
}
