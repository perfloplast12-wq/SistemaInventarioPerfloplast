<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Limpiar Producciones
        DB::table('production_items')->truncate();
        DB::table('productions')->truncate();

        // 2. Limpiar Movimientos de Inventario
        DB::table('inventory_movements')->truncate();

        // 3. Limpiar Stock (Poner todo en cero realmente eliminando los registros de existencia)
        DB::table('stocks')->truncate();

        // 4. Limpiar Pedidos y Despachos (Para evitar datos huérfanos)
        DB::table('orders')->truncate();
        DB::table('dispatches')->truncate();
        
        // 5. Limpiar Ventas y Pagos si existen
        if (Schema::hasTable('sale_items')) DB::table('sale_items')->truncate();
        if (Schema::hasTable('payments')) DB::table('payments')->truncate();
        if (Schema::hasTable('sales')) DB::table('sales')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
