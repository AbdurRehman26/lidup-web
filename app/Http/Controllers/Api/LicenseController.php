<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LicenseController extends Controller
{
    #[OA\Get(
        path: '/license/validate',
        operationId: 'validateLicense',
        summary: 'Validate a license key',
        description: 'Returns the current paid or trial entitlement without activating a device.',
        security: [['licenseKey' => []]],
        tags: ['License'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The license is active.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean', example: true),
                        new OA\Property(property: 'reason', type: 'string', nullable: true, example: null),
                        new OA\Property(property: 'plan', type: 'string', nullable: true, example: 'personal'),
                        new OA\Property(property: 'subscription_status', type: 'string', example: 'trialing'),
                        new OA\Property(property: 'entitlement_source', type: 'string', enum: ['subscription', 'trial'], example: 'trial'),
                        new OA\Property(property: 'entitled_until', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'device_limit', type: 'integer', example: 1),
                        new OA\Property(property: 'active_devices', type: 'integer', example: 0),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'The activation key is missing or invalid.'),
            new OA\Response(response: 403, description: 'The key lacks permission or its entitlement is inactive.'),
        ],
    )]
    #[OA\Post(
        path: '/license/validate',
        operationId: 'validateLicensePost',
        summary: 'Validate a license key using POST',
        description: 'POST-compatible version of license validation for native clients.',
        security: [['licenseKey' => []]],
        tags: ['License'],
        responses: [
            new OA\Response(response: 200, description: 'The license is active.'),
            new OA\Response(response: 401, description: 'The activation key is missing or invalid.'),
            new OA\Response(response: 403, description: 'The key lacks permission or its entitlement is inactive.'),
        ],
    )]
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
