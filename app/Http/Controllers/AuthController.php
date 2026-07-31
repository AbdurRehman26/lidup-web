<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ApiKeyService;
use App\Services\SubscriptionPackageService;
use App\Services\TrialService;
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
        $plans = $packages->paidPlans();
        $selectedPlan = array_key_exists($request->string('plan')->toString(), $plans)
            ? $request->string('plan')->toString()
            : array_key_first($plans);

        $offer = $trials->currentOffer();

        return Inertia::render('Register', [
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
            'trialOffer' => $trials->present($offer),
            'packages' => $trials->publicPackages()->map(fn ($package) => $trials->present($package))->values(),
        ]);
    }

    public function register(
        Request $request,
        ApiKeyService $apiKeys,
        TrialService $trials,
        SubscriptionPackageService $packages,
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan' => ['required', Rule::in(array_keys($packages->paidPlans()))],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $selectedPackage = $packages->paidPackages()->firstWhere('slug', $validated['plan']);
        $trialAssigned = $trials->assignIfEligible($user, $selectedPackage->plan);
        $createdKey = $apiKeys->create($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route($trialAssigned ? 'dashboard' : 'subscription.show', $trialAssigned ? [] : ['plan' => $validated['plan']])
            ->with('plain_api_key', $createdKey['plain_text'])
            ->with('api_key_message', $trialAssigned
                ? 'Your free trial and activation key are ready. Copy the key now—it will only be shown once.'
                : 'Your activation key is ready. Choose a plan to activate it.');
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
