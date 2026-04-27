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
    // Force clear all caches
    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $results['0_cache_status'] = 'CLEARED: ' . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) { $results['0_cache_status'] = 'ERROR: '.$e->getMessage(); }

    $results['0_env'] = [
        'app_debug' => config('app.debug'),
        'app_env' => config('app.env'),
        'php_version' => PHP_VERSION,
    ];

    try { $results['1_product_count'] = \App\Models\Product::count(); } 
    catch (\Exception $e) { $results['1_product_count'] = 'ERROR: '.$e->getMessage(); }
    
    try { $results['2_raw_materials'] = \App\Models\Product::where('type','raw_material')->count(); } 
    catch (\Exception $e) { $results['2_raw_materials'] = 'ERROR: '.$e->getMessage(); }
    
    try {
        $results['3_critical_stock'] = \Illuminate\Support\Facades\DB::table('products')
            ->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.units_per_presentation')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) / COALESCE(NULLIF(products.units_per_presentation, 0), 1) <= 10')
            ->get(['products.id'])
            ->count();
    } catch (\Exception $e) { $results['3_critical_stock'] = 'ERROR: '.$e->getMessage(); }
    
    try { $results['4_pending_orders'] = \App\Models\Order::where('status','pending')->count(); } 
    catch (\Exception $e) { $results['4_pending_orders'] = 'ERROR: '.$e->getMessage(); }
    
    try { $results['5_movements_24h'] = \App\Models\InventoryMovement::where('created_at','>=',now()->subDay())->count(); } 
    catch (\Exception $e) { $results['5_movements_24h'] = 'ERROR: '.$e->getMessage(); }
    
    try { $results['6_warehouses'] = \App\Models\Warehouse::where('is_active',true)->count(); } 
    catch (\Exception $e) { $results['6_warehouses'] = 'ERROR: '.$e->getMessage(); }
    
    try { $results['7_recent_movements'] = \App\Models\InventoryMovement::latest()->take(8)->count(); } 
    catch (\Exception $e) { $results['7_recent_movements'] = 'ERROR: '.$e->getMessage(); }

    try {
        $results['8_critical_ids'] = \Illuminate\Support\Facades\DB::table('products')
            ->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.units_per_presentation')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) / COALESCE(NULLIF(products.units_per_presentation, 0), 1) <= 10')
            ->take(5)
            ->pluck('products.id')
            ->toArray();
    } catch (\Exception $e) { $results['8_critical_ids'] = 'ERROR: '.$e->getMessage(); }

    try {
        $results['9_sale_quick_url'] = \App\Filament\Resources\SaleResource::getUrl('quick-sale');
    } catch (\Exception $e) { $results['9_sale_quick_url'] = 'ERROR: '.$e->getMessage(); }

    return response()->json($results, 200, [], JSON_PRETTY_PRINT);
});

Route::get('/force-migrate', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return "Migración exitosa: " . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        return "Error en migración: " . $e->getMessage();
    }
});
