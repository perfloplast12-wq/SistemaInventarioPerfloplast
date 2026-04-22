<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TrackingController;

Route::post('/tracking', [TrackingController::class, 'store'])
    ->name('api.tracking.store');

Route::get('/tracking/{dispatch}', [TrackingController::class, 'show'])
    ->name('api.tracking.show');
