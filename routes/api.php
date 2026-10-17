<?php

use App\Http\Controllers\Api\BiometricPunchController;
use Illuminate\Support\Facades\Route;

Route::post('biometric/punch', [BiometricPunchController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('biometric.punch');
