<?php

namespace App\Http\Controllers;

use App\Models\ProductUpdate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'subscription' => $request->user()->subscription,
            'updates' => ProductUpdate::query()->published()->latest('published_at')->limit(6)->get(),
        ]);
    }
}
