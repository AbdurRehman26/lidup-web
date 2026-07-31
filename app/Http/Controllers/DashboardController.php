<?php

namespace App\Http\Controllers;

use App\Models\ProductUpdate;
use App\Models\Release;
use App\Services\TrialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TrialService $trials): Response
    {
        $request->user()->load('subscriptionPackage');
        $assignedPackage = $request->user()->subscriptionPackage;
        $assignedUsersCount = $assignedPackage?->users()->count();
        $activeKey = $request->user()->tokens()->latest()->first();

        $latestRelease = Release::query()
            ->available()
            ->where('is_current', true)
            ->latest('published_at')
            ->first();

        return Inertia::render('Dashboard', [
            'subscription' => $request->user()->appSubscription,
            'trial' => [
                'active' => $request->user()->onAppTrial(),
                'status' => $request->user()->entitlementStatus(),
                'plan' => $request->user()->entitlementPlan(),
                'started_at' => $request->user()->trial_started_at,
                'ends_at' => $request->user()->trial_ends_at,
                'cohort_position' => $request->user()->trial_cohort_position,
                'package' => $assignedPackage ? [
                    'id' => $assignedPackage->id,
                    'slug' => $assignedPackage->slug,
                    'name' => $assignedPackage->name,
                    'description' => $assignedPackage->description,
                    'duration' => $assignedPackage->durationLabel(),
                    'device_limit' => $assignedPackage->device_limit,
                    'user_limit' => $assignedPackage->user_limit,
                    'users_count' => $assignedUsersCount,
                    'remaining_spots' => $assignedPackage->user_limit === null
                        ? null
                        : max(0, $assignedPackage->user_limit - $assignedUsersCount),
                    'is_paid' => $assignedPackage->is_paid,
                    'is_active' => $assignedPackage->is_active,
                    'is_visible' => $assignedPackage->is_visible,
                ] : null,
            ],
            'earlyBirdPackages' => $trials->publicPackages()
                ->where('is_paid', false)
                ->map(fn ($package) => $trials->present($package))
                ->values(),
            'apiKey' => [
                'prefix' => 'lidup_',
                'exists' => $activeKey !== null,
                'created_at' => $activeKey?->created_at,
                'last_used_at' => $activeKey?->last_used_at,
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
