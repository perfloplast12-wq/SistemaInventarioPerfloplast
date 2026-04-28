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

Route::get('/force-migrate', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return "Migración exitosa: " . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        return "Error en migración: " . $e->getMessage();
    }
});
