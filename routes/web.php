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

Route::get('/debug-form', [\App\Http\Controllers\DebugController::class, 'debugForm']);

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

                $minutesAgo = $lastLocation->created_at 
                    ? (int) now()->diffInMinutes($lastLocation->created_at) 
                    : 9999;
                $isOnline = $minutesAgo <= 5;

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'lat' => (float) $lastLocation->lat,
                    'lng' => (float) $lastLocation->lng,
                    'speed' => (float) ($lastLocation->speed ?? 0),
                    'updated_at' => $lastLocation->created_at ? $lastLocation->created_at->diffForHumans() : 'Desconocido',
                    'last_seen_exact' => $lastLocation->created_at ? $lastLocation->created_at->format('d/m/Y h:i:s A') : 'Desconocido',
                    'accuracy' => round((float) ($lastLocation->accuracy ?? 0), 1),
                    'is_online' => $isOnline,
                ];
            } catch (\Exception $e) { return null; }
        })->filter()->values();

        return response()->json($locations);
    } catch (\Exception $e) {
        return response()->json([]);
    }
})->middleware(['web', 'auth'])->name('web.sales-locations');

Route::get('/force-migrate', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return "Migración exitosa: " . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        return "Error en migración: " . $e->getMessage();
    }
});
