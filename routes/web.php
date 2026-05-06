<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/admin/invoices/{invoice}/print', function (\App\Models\Invoice $invoice) {
    return view('invoices.print', compact('invoice'));
})->name('invoices.print')->middleware(['auth']);

Route::get('/admin/sales/{sale}/pdf', [\App\Http\Controllers\SalePdfController::class, 'download'])
    ->name('sales.invoice.pdf')
    ->middleware(['auth']);

Route::get('/debug-form', [\App\Http\Controllers\DebugController::class, 'debugSaleCreate']);
Route::get('/debug-sale-form', [\App\Http\Controllers\DebugController::class, 'debugSaleForm']);
Route::get('/debug-log', [\App\Http\Controllers\DebugController::class, 'viewLog']);
Route::get('/run-migrations', [\App\Http\Controllers\DebugController::class, 'runMigrations']);

Route::get('/admin/injection-reports/{report}/pdf', [\App\Http\Controllers\InjectionReportPdfController::class, 'download'])
    ->name('injection-reports.pdf')
    ->middleware(['auth']);

Route::get('/diag-inventario', function () {
    return "OK - Diagnóstico Activo (v2 - " . now()->toDateTimeString() . ")";
});

// Ruta de tracking GPS con sesión web para que auth()->user() funcione
Route::post('/api/tracking', [\App\Http\Controllers\Api\TrackingController::class, 'store'])
    ->middleware(['web', 'auth'])
    ->name('web.tracking.store');

// Polling endpoint: get the latest dispatch location for real-time map updates
Route::get('/api/dispatch-location/{dispatch}/latest', function (\App\Models\Dispatch $dispatch) {
    // 1. Get all active dispatches for the same truck (in progress)
    $activeDispatchIds = [];
    if ($dispatch->truck_id) {
        $activeDispatchIds = \App\Models\Dispatch::where('truck_id', $dispatch->truck_id)
            ->where('status', 'in_progress')
            ->pluck('id')
            ->toArray();
    }

    // Always include the current dispatch ID in our search
    if (!in_array($dispatch->id, $activeDispatchIds)) {
        $activeDispatchIds[] = $dispatch->id;
    }

    // 2. Query each active dispatch ID individually (perfectly indexed, ultra-fast)
    $lastLocation = null;
    foreach ($activeDispatchIds as $aid) {
        $loc = \App\Models\DispatchLocation::where('dispatch_id', $aid)
            ->orderByDesc('created_at')
            ->first();
        if ($loc) {
            if (!$lastLocation || $loc->created_at->gt($lastLocation->created_at)) {
                $lastLocation = $loc;
            }
        }
    }

    // 3. Fallback: if no active dispatches have coordinates, check the 5 most recent dispatches for this truck
    if (!$lastLocation && $dispatch->truck_id) {
        $otherIds = \App\Models\Dispatch::where('truck_id', $dispatch->truck_id)
            ->whereNotIn('id', $activeDispatchIds)
            ->orderByDesc('id')
            ->limit(5)
            ->pluck('id');

        foreach ($otherIds as $oid) {
            $loc = \App\Models\DispatchLocation::where('dispatch_id', $oid)
                ->orderByDesc('created_at')
                ->first();
            if ($loc) {
                if (!$lastLocation || $loc->created_at->gt($lastLocation->created_at)) {
                    $lastLocation = $loc;
                }
            }
        }
    }

    if (!$lastLocation) {
        return response()->json(null);
    }

    $isOfflineSignal = ($lastLocation->speed == -1);

    // 4. Handle offline signal check across all searched dispatches
    $displayLocation = $lastLocation;
    if ($isOfflineSignal) {
        $realLocation = null;
        // Search active ones first
        foreach ($activeDispatchIds as $aid) {
            $loc = \App\Models\DispatchLocation::where('dispatch_id', $aid)
                ->where('speed', '!=', -1)
                ->orderByDesc('created_at')
                ->first();
            if ($loc) {
                if (!$realLocation || $loc->created_at->gt($realLocation->created_at)) {
                    $realLocation = $loc;
                }
            }
        }
        // Then fallback ones if still null
        if (!$realLocation && $dispatch->truck_id) {
            foreach ($otherIds as $oid) {
                $loc = \App\Models\DispatchLocation::where('dispatch_id', $oid)
                    ->where('speed', '!=', -1)
                    ->orderByDesc('created_at')
                    ->first();
                if ($loc) {
                    if (!$realLocation || $loc->created_at->gt($realLocation->created_at)) {
                        $realLocation = $loc;
                    }
                }
            }
        }
        if ($realLocation) {
            $displayLocation = $realLocation;
        }
    }

    $secsSince = $displayLocation->created_at ? now()->diffInSeconds($displayLocation->created_at) : 999;

    return response()->json([
        'lat' => (float) $displayLocation->lat,
        'lng' => (float) $displayLocation->lng,
        'speed' => (float) ($displayLocation->speed ?? 0),
        'heading' => (float) ($displayLocation->heading ?? 0),
        'timestamp' => $displayLocation->created_at?->toIso8601String(),
        'last_seen_exact' => $displayLocation->created_at?->format('h:i:s A'),
        'is_offline' => $isOfflineSignal || $secsSince > 120,
    ]);
})->middleware(['web', 'auth'])->name('web.dispatch-location.latest');

// Ruta para obtener ubicaciones de vendedores (actualización silenciosa del mapa)
Route::get('/api/sales-locations', function () {
    try {
        $userIdsWithLocation = \App\Models\UserLocation::select('user_id')->distinct()->pluck('user_id');
        $salesUsers = \App\Models\User::whereIn('id', $userIdsWithLocation)->get();

        $locations = $salesUsers->map(function ($user) {
            try {
                $lastLocation = \App\Models\UserLocation::where('user_id', $user->id)
                    ->latest('id')
                    ->first();
                if (!$lastLocation || !$lastLocation->lat || !$lastLocation->lng) return null;

                $isOfflineSignal = ($lastLocation->accuracy == -1);
                
                if ($isOfflineSignal) {
                    $realLocation = \App\Models\UserLocation::where('user_id', $user->id)
                        ->where('accuracy', '!=', -1)
                        ->latest('id')
                        ->first();
                    $displayLocation = $realLocation ?? $lastLocation;
                } else {
                    $displayLocation = $lastLocation;
                }

                $createdAt = $displayLocation->created_at;
                if ($createdAt) {
                    $minutesAgo = (int) $createdAt->diffInMinutes(now());
                    $isOnline = !$isOfflineSignal && $minutesAgo <= 2;
                } else {
                    $localTime = null;
                    $isOnline = false;
                }

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'lat' => (float) $displayLocation->lat,
                    'lng' => (float) $displayLocation->lng,
                    'speed' => (float) ($displayLocation->speed ?? 0),
                    'updated_at' => $localTime ? $localTime->diffForHumans() : 'Desconocido',
                    'last_seen_exact' => $localTime ? $localTime->format('d/m/Y h:i:s A') : 'Desconocido',
                    'accuracy' => $isOfflineSignal ? 0 : round((float) ($displayLocation->accuracy ?? 0), 1),
                    'is_online' => $isOnline,
                ];
            } catch (\Exception $e) { return null; }
        })->filter()->values();

        return response()->json($locations);
    } catch (\Exception $e) {
        return response()->json([]);
    }
})->middleware(['web', 'auth'])->name('web.sales-locations');

// Rutas de mantenimiento eliminadas de aquí por seguridad (ahora están protegidas en api.php)

