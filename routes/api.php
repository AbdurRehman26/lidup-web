<?php

use App\Http\Controllers\Api\ActivationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['throttle:60,1', 'auth:sanctum'])
    ->group(function () {
        Route::post('/activation/verify', [ActivationController::class, 'verify']);
        Route::delete('/activation/{deviceId}', [ActivationController::class, 'deactivate']);
    });
