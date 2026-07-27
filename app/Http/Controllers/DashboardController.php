<?php

namespace App\Http\Controllers;

use App\Models\ProductUpdate;
use App\Models\Release;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ApiKeyService $apiKeys): Response
    {
        $activeKey = $request->user()->tokens()->latest()->first();

        if (! $activeKey) {
            $created = $apiKeys->create($request->user());
            $activeKey = $created['key'];
            session()->flash('plain_api_key', $created['plain_text']);
            session()->flash('api_key_message', 'Your activation key is ready. Copy it now—it will only be shown once.');
        }

        $latestRelease = Release::query()
            ->available()
            ->where('is_current', true)
            ->latest('published_at')
            ->first();

        return Inertia::render('Dashboard', [
            'subscription' => $request->user()->appSubscription,
            'plans' => config('plans'),
            'apiKey' => [
                'prefix' => 'lidup_',
                'created_at' => $activeKey->created_at,
                'last_used_at' => $activeKey->last_used_at,
            ],
            'activations' => $request->user()->appActivations()
                ->active()
                ->latest('last_verified_at')
                ->get(['id', 'device_id', 'device_name', 'app_version', 'activated_at', 'last_verified_at']),
            'latestRelease' => $latestRelease ? [
                'version' => $latestRelease->version,
                'published_at' => $latestRelease->published_at,
                'available' => Storage::disk('local')->exists($latestRelease->file_path),
            ] : null,
            'updates' => ProductUpdate::query()->published()->latest('published_at')->limit(6)->get(),
        ]);
    }
}
