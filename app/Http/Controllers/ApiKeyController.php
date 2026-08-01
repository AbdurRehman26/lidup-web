<?php

namespace App\Http\Controllers;

use App\Services\ApiKeyService;
use App\Services\TrialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiKeyController extends Controller
{
    public function store(Request $request, ApiKeyService $apiKeys, TrialService $trials): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $apiKeys, $trials): ?array {
            $user = $request->user()->newQuery()->lockForUpdate()->findOrFail($request->user()->getKey());

            if ($user->tokens()->exists()) {
                return null;
            }

            $trialAssigned = $user->onAppTrial()
                || $trials->assignIfEligible($user, $user->trial_plan ?? 'personal');

            return [
                'trial_assigned' => $trialAssigned,
                'key' => $apiKeys->create($user),
            ];
        });

        if ($result === null) {
            return back()->with('api_key_message', 'Your account already has an activation key.');
        }

        return back()
            ->with('plain_api_key', $result['key']['plain_text'])
            ->with('api_key_message', $result['trial_assigned']
                ? 'Your activation key and early-bird access are ready. The key will remain available on your dashboard.'
                : 'Your activation key was created, but no early-bird places are currently available.');
    }

    public function rotate(Request $request, ApiKeyService $apiKeys): RedirectResponse
    {
        $created = $apiKeys->rotate($request->user());

        return back()
            ->with('plain_api_key', $created['plain_text'])
            ->with('api_key_message', 'A new activation key was created and will remain available here. Your previous key and devices were revoked.');
    }

    public function destroy(Request $request, ApiKeyService $apiKeys): RedirectResponse
    {
        $apiKeys->revoke($request->user());

        return back()->with('api_key_message', 'Your activation key and connected devices were revoked.');
    }
}
