<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class ActivationController extends Controller
{
    #[OA\Post(
        path: '/activation/verify',
        operationId: 'verifyActivation',
        summary: 'Activate or verify a Mac',
        security: [['licenseKey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['device_id'],
                properties: [
                    new OA\Property(property: 'device_id', type: 'string', maxLength: 191, example: 'macbook-pro-hardware-id'),
                    new OA\Property(property: 'device_name', type: 'string', maxLength: 120, nullable: true, example: 'Work MacBook Pro'),
                    new OA\Property(property: 'app_version', type: 'string', maxLength: 40, nullable: true, example: '1.2.0'),
                ],
            ),
        ),
        tags: ['Activation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The Mac is activated and the entitlement is valid.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean', example: true),
                        new OA\Property(property: 'activation_id', type: 'integer', example: 42),
                        new OA\Property(property: 'plan', type: 'string', example: 'personal'),
                        new OA\Property(property: 'subscription_status', type: 'string', example: 'active'),
                        new OA\Property(property: 'entitlement_source', type: 'string', enum: ['subscription', 'trial']),
                        new OA\Property(property: 'entitled_until', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'device_limit', type: 'integer', example: 1),
                        new OA\Property(property: 'active_devices', type: 'integer', example: 1),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'The activation key is missing or invalid.'),
            new OA\Response(response: 403, description: 'The entitlement is inactive or the key lacks permission.'),
            new OA\Response(response: 409, description: 'The package device limit has been reached.'),
            new OA\Response(response: 422, description: 'The device details are invalid.'),
        ],
    )]
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
        $plan = $user->entitlementPlan();

        if (! $user->hasAppEntitlement()) {
            return response()->json([
                'valid' => false,
                'reason' => 'subscription_inactive',
                'message' => 'An active subscription or trial is required.',
            ], 403);
        }

        $deviceLimit = $user->onAppTrial() && $user->subscriptionPackage
            ? $user->subscriptionPackage->device_limit
            : (int) data_get(config('plans'), "{$plan}.devices", 1);
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
            'plan' => $plan,
            'subscription_status' => $user->entitlementStatus(),
            'entitlement_source' => $subscription?->isEntitled() === true ? 'subscription' : 'trial',
            'entitled_until' => $user->entitlementEndsAt()?->toIso8601String(),
            'device_limit' => $deviceLimit,
            'active_devices' => $user->appActivations()->active()->count(),
        ]);
    }

    #[OA\Delete(
        path: '/activation/{deviceId}',
        operationId: 'deactivateDevice',
        summary: 'Deactivate a Mac',
        security: [['licenseKey' => []]],
        tags: ['Activation'],
        parameters: [
            new OA\Parameter(
                name: 'deviceId',
                description: 'The device identifier originally used during activation.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                example: 'macbook-pro-hardware-id',
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The Mac was deactivated.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'deactivated', type: 'boolean', example: true),
                        new OA\Property(property: 'device_id', type: 'string', example: 'macbook-pro-hardware-id'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'The activation key is missing or invalid.'),
            new OA\Response(response: 403, description: 'The key cannot deactivate devices.'),
            new OA\Response(response: 404, description: 'No active device matches the identifier.'),
        ],
    )]
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
