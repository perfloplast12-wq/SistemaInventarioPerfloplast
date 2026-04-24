<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CatalogController;

Route::get('/catalog', [CatalogController::class, 'index'])
    ->name('api.catalog.index');

Route::post('/tracking', [TrackingController::class, 'store'])
    ->name('api.tracking.store');

Route::get('/tracking/{dispatch}', [TrackingController::class, 'show'])
    ->name('api.tracking.show');
