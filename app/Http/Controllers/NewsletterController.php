<?php

namespace App\Http\Controllers;

use App\Models\UpdateSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        UpdateSubscriber::query()->updateOrCreate(
            ['email' => $validated['email']],
            ['subscribed_at' => now()]
        );

        return back()->with('subscribed', 'You’re on the list. We’ll only send meaningful updates.');
    }
}
