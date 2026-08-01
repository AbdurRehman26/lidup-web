<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\SubscriptionController;
use App\Services\TrialService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn (TrialService $trials) => Inertia::render('Home', [
    'trialOffer' => $trials->present($trials->currentOffer()),
    'packages' => $trials->publicPackages()->map(fn ($package) => $trials->present($package))->values(),
]))->name('home');
Route::get('/faqs', fn () => Inertia::render('Faqs'))->name('faqs');
Route::get('/roadmap', [RoadmapController::class, 'index'])->name('roadmap');
Route::post('/roadmap', [RoadmapController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('roadmap.store');
Route::get('/download', [DownloadController::class, 'index'])->name('download');
Route::get('/download/latest', [DownloadController::class, 'latest'])
    ->middleware('throttle:30,1')
    ->name('download.latest');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
        Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
        Route::patch('/subscription', [SubscriptionController::class, 'update'])->name('subscription.update');
        Route::delete('/subscription', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::post('/api-key', [ApiKeyController::class, 'store'])->name('api-key.store');
        Route::put('/api-key', [ApiKeyController::class, 'rotate'])->name('api-key.rotate');
        Route::delete('/api-key', [ApiKeyController::class, 'destroy'])->name('api-key.destroy');
        Route::delete('/devices/{activation}', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::post('/roadmap/{feedbackItem}/vote', [RoadmapController::class, 'vote'])->name('roadmap.vote');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
