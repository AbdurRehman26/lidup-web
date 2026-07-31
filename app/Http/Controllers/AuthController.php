<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SubscriptionPackageService;
use App\Services\TrialService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showRegister(Request $request, TrialService $trials, SubscriptionPackageService $packages): Response
    {
        $plans = $packages->paidPlans(false);
        $selectedPlan = array_key_exists($request->string('plan')->toString(), $plans)
            ? $request->string('plan')->toString()
            : array_key_first($plans);

        $offer = $trials->currentOffer();

        return Inertia::render('Register', [
            'selectedPlan' => $selectedPlan,
            'trialOffer' => $trials->present($offer),
            'packages' => $trials->publicPackages()->map(fn ($package) => $trials->present($package))->values(),
        ]);
    }

    public function register(
        Request $request,
        SubscriptionPackageService $packages,
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan' => ['required', Rule::in(array_keys($packages->paidPlans(false)))],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $selectedPackage = $packages->paidPackages(false)->firstWhere('slug', $validated['plan']);
        $user->forceFill(['trial_plan' => $selectedPackage->plan])->save();
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('verification.notice')
            ->with('status', 'account-created');
    }

    public function showLogin(): Response
    {
        return Inertia::render('Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those details do not match an account.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
