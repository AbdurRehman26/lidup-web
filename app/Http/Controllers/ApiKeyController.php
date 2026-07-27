<?php

namespace App\Http\Controllers;

use App\Services\ApiKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function store(Request $request, ApiKeyService $apiKeys): RedirectResponse
    {
        $created = $apiKeys->create($request->user());

        return back()
            ->with('plain_api_key', $created['plain_text'])
            ->with('api_key_message', 'Your activation key was created. Copy it now.');
    }

    public function rotate(Request $request, ApiKeyService $apiKeys): RedirectResponse
    {
        $created = $apiKeys->rotate($request->user());

        return back()
            ->with('plain_api_key', $created['plain_text'])
            ->with('api_key_message', 'A new activation key was created. Your previous key and devices were revoked.');
    }

    public function destroy(Request $request, ApiKeyService $apiKeys): RedirectResponse
    {
        $apiKeys->revoke($request->user());

        return back()->with('api_key_message', 'Your activation key and connected devices were revoked.');
    }
}
