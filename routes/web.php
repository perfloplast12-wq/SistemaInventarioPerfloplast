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

Route::get('/force-migrate', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return "Migración exitosa: " . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        return "Error en migración: " . $e->getMessage();
    }
});
Route::get('/debug-log', function () {
    if (config('app.debug') || auth()->check()) {
        $path = storage_path('logs/laravel.log');
        if (!file_exists($path)) return "Log file not found at $path";
        return nl2br(file_get_contents($path, false, null, -5000));
    }
    return "Unauthorized";
});
