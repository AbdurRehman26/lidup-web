<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class ActivationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:191'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:40'],
        ]);

        $accessToken = $this->personalAccessToken($request);

        if (! $accessToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $accessToken->tokenable;

        if (! $accessToken->can('activation:verify')) {
            return response()->json([
                'valid' => false,
                'reason' => 'ability_denied',
                'message' => 'This token cannot verify app activations.',
            ], 403);
        }

        $subscription = $user->appSubscription;

        if (! $subscription || ! $subscription->isEntitled()) {
            return response()->json([
                'valid' => false,
                'reason' => 'subscription_inactive',
                'message' => 'An active subscription or trial is required.',
            ], 403);
        }

        $deviceLimit = (int) data_get(config('plans'), "{$subscription->plan}.devices", 1);
        $existing = $user->appActivations()->where('device_id', $validated['device_id'])->first();
        $activeDevices = $user->appActivations()->active()->count();

        if ((! $existing || $existing->revoked_at) && $activeDevices >= $deviceLimit) {
            return response()->json([
                'valid' => false,
                'reason' => 'device_limit_reached',
                'message' => "This plan allows {$deviceLimit} active device(s).",
                'device_limit' => $deviceLimit,
            ], 409);
        }

        $activation = DB::transaction(function () use ($user, $accessToken, $validated) {
            return $user->appActivations()->updateOrCreate(
                ['device_id' => $validated['device_id']],
                [
                    'personal_access_token_id' => $accessToken->id,
                    'device_name' => $validated['device_name'] ?? null,
                    'app_version' => $validated['app_version'] ?? null,
                    'activated_at' => now(),
                    'last_verified_at' => now(),
                    'revoked_at' => null,
                ],
            );
        });

        return response()->json([
            'valid' => true,
            'activation_id' => $activation->id,
            'plan' => $subscription->plan,
            'subscription_status' => $subscription->status,
            'entitled_until' => ($subscription->ends_at ?? $subscription->trial_ends_at)?->toIso8601String(),
            'device_limit' => $deviceLimit,
            'active_devices' => $user->appActivations()->active()->count(),
        ]);
    }

    public function deactivate(Request $request, string $deviceId): JsonResponse
    {
        $accessToken = $this->personalAccessToken($request);

        if (! $accessToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $accessToken->can('activation:deactivate')) {
            return response()->json([
                'message' => 'This token cannot deactivate devices.',
            ], 403);
        }

        $activation = $accessToken->tokenable
            ->appActivations()
            ->where('device_id', $deviceId)
            ->active()
            ->firstOrFail();

        $activation->update(['revoked_at' => now()]);

        return response()->json([
            'deactivated' => true,
            'device_id' => $deviceId,
        ]);
    }

    private function personalAccessToken(Request $request): ?PersonalAccessToken
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return null;
        }

        return PersonalAccessToken::findToken($plainTextToken);
    }
}
