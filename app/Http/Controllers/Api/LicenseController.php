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
        $valid = (bool) $subscription?->isEntitled();
        $deviceLimit = $subscription
            ? (int) data_get(config('plans'), "{$subscription->plan}.devices", 0)
            : 0;

        return response()->json([
            'valid' => $valid,
            'reason' => $valid ? null : 'subscription_inactive',
            'plan' => $subscription?->plan,
            'subscription_status' => $subscription?->status ?? 'inactive',
            'entitled_until' => ($subscription?->ends_at ?? $subscription?->trial_ends_at)?->toIso8601String(),
            'device_limit' => $deviceLimit,
            'active_devices' => $user->appActivations()->active()->count(),
        ], $valid ? 200 : 403);
    }
}
