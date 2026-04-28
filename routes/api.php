<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\TrackingController;

Route::get('/catalog', [CatalogController::class, 'index'])
    ->name('api.catalog.index');

// POST /api/tracking se movió a web.php para que auth()->user() funcione con la sesión

Route::get('/tracking/{dispatch}', [TrackingController::class, 'show'])
    ->name('api.tracking.show');
