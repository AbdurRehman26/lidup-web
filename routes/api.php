<?php

use App\Http\Controllers\Api\ActivationController;
use App\Http\Controllers\Api\LicenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['throttle:60,1', 'auth:sanctum'])
    ->group(function () {
        Route::match(['get', 'post'], '/license/validate', LicenseController::class);
        Route::post('/activation/verify', [ActivationController::class, 'verify']);
        Route::delete('/activation/{deviceId}', [ActivationController::class, 'deactivate']);
    });
