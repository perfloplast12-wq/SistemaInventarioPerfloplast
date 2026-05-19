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

    // 2. Query con whereIn para eliminar el foreach N+1
    $lastLocation = \App\Models\DispatchLocation::whereIn('dispatch_id', $activeDispatchIds)
        ->orderByDesc('created_at')
        ->first();

    // 3. Fallback: if no active dispatches have coordinates, check the 5 most recent dispatches for this truck
    $otherIds = [];
    if (!$lastLocation && $dispatch->truck_id) {
        $otherIds = \App\Models\Dispatch::where('truck_id', $dispatch->truck_id)
            ->whereNotIn('id', $activeDispatchIds)
            ->orderByDesc('id')
            ->limit(5)
            ->pluck('id')
            ->toArray();

        if (!empty($otherIds)) {
            $lastLocation = \App\Models\DispatchLocation::whereIn('dispatch_id', $otherIds)
                ->orderByDesc('created_at')
                ->first();
        }
    }

    if (!$lastLocation) {
        return response()->json(null);
    }

    $isOfflineSignal = ($lastLocation->speed == -1);

    // 4. Handle offline signal check
    $displayLocation = $lastLocation;
    if ($isOfflineSignal) {
        $realLocation = \App\Models\DispatchLocation::whereIn('dispatch_id', $activeDispatchIds)
            ->where('speed', '!=', -1)
            ->orderByDesc('created_at')
            ->first();
            
        // Then fallback ones if still null
        if (!$realLocation && !empty($otherIds)) {
            $realLocation = \App\Models\DispatchLocation::whereIn('dispatch_id', $otherIds)
                ->where('speed', '!=', -1)
                ->orderByDesc('created_at')
                ->first();
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
        $latestLocationIds = \App\Models\UserLocation::selectRaw('MAX(id) as id')
            ->groupBy('user_id')
            ->pluck('id');
            
        $latestLocations = \App\Models\UserLocation::whereIn('id', $latestLocationIds)
            ->get()
            ->keyBy('user_id');
            
        $realLocationIds = \App\Models\UserLocation::selectRaw('MAX(id) as id')
            ->where('accuracy', '!=', -1)
            ->groupBy('user_id')
            ->pluck('id');
            
        $realLocations = \App\Models\UserLocation::whereIn('id', $realLocationIds)
            ->get()
            ->keyBy('user_id');

        $userIdsWithLocation = $latestLocations->keys();
        $salesUsers = \App\Models\User::whereIn('id', $userIdsWithLocation)->get();

        $locations = $salesUsers->map(function ($user) use ($latestLocations, $realLocations) {
            try {
                $lastLocation = $latestLocations->get($user->id);
                if (!$lastLocation || !$lastLocation->lat || !$lastLocation->lng) return null;

                $isOfflineSignal = ($lastLocation->accuracy == -1);
                
                if ($isOfflineSignal) {
                    $realLocation = $realLocations->get($user->id);
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
                    'updated_at' => $createdAt ? $createdAt->diffForHumans() : 'Desconocido',
                    'last_seen_exact' => $createdAt ? $createdAt->format('d/m/Y h:i:s A') : 'Desconocido',
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

