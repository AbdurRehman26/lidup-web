<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->only('id', 'name', 'email', 'email_verified_at', 'created_at'),
            ],
            'flash' => [
                'subscription_updated' => fn () => $request->session()->get('subscription_updated'),
                'plain_api_key' => fn () => $request->session()->get('plain_api_key'),
                'api_key_message' => fn () => $request->session()->get('api_key_message'),
                'device_message' => fn () => $request->session()->get('device_message'),
                'feedback_message' => fn () => $request->session()->get('feedback_message'),
            ],
        ];
    }
}
