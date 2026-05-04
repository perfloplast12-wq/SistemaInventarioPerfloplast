<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\TrackingController;

Route::get('/catalog', [CatalogController::class, 'index'])
    ->name('api.catalog.index');

// POST /api/tracking se movió a web.php para que auth()->user() funcione con la sesión

Route::get('/tracking/{dispatch}', [TrackingController::class, 'show'])
    ->middleware(['web', 'auth'])
    ->name('api.tracking.show');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/force-migrate', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            return "Migración exitosa: " . \Illuminate\Support\Facades\Artisan::output();
        } catch (\Exception $e) {
            return "Error en migración: " . $e->getMessage();
        }
    });

    Route::get('/sync-db-productions', function () {
        try {
            // 1. Agregar columnas a la tabla de items
            \Illuminate\Support\Facades\Schema::table('production_items', function (\Illuminate\Database\Schema\Blueprint $table) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('production_items', 'type')) {
                    $table->string('type')->default('consumable')->after('product_id');
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('production_items', 'color_id')) {
                    $table->foreignId('color_id')->nullable()->after('product_id')->constrained('colors')->nullOnDelete();
                }
            });

            // 2. Limpiar la tabla principal
            \Illuminate\Support\Facades\Schema::table('productions', function (\Illuminate\Database\Schema\Blueprint $table) {
                // Primero quitamos las llaves foráneas
                if (\Illuminate\Support\Facades\Schema::hasColumn('productions', 'product_id')) {
                    $table->dropForeign(['product_id']);
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('productions', 'color_id')) {
                    $table->dropForeign(['color_id']);
                }
                
                // Ahora sí borramos las columnas
                $columns = ['product_id', 'color_id', 'quantity'];
                foreach ($columns as $col) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('productions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });


            return "✅ Base de datos de Producción sincronizada con éxito. Ya puedes cerrar esta pestaña.";
        } catch (\Exception $e) {
            return "❌ Error: " . $e->getMessage();
        }
    });
});
