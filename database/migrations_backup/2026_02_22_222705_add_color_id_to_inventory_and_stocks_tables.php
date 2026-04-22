<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. inventory_movements
        if (!Schema::hasColumn('inventory_movements', 'color_id')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->foreignId('color_id')->nullable()->after('product_id')->constrained('colors')->nullOnDelete();
            });
        }

        // 2. stocks
        if (!Schema::hasColumn('stocks', 'color_id')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->foreignId('color_id')->nullable()->after('product_id')->constrained('colors')->cascadeOnDelete();
            });
        }

        // Borrar indices con SQL directo para ignorar errores si no existen
        try {
            DB::statement('ALTER TABLE stocks DROP INDEX stocks_product_warehouse_unique');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE stocks DROP INDEX stocks_product_truck_unique');
        } catch (\Exception $e) {}

        Schema::table('stocks', function (Blueprint $table) {
            // Recrear con color_id
            $table->unique(['product_id', 'warehouse_id', 'color_id'], 'stocks_product_warehouse_color_unique');
            $table->unique(['product_id', 'truck_id', 'color_id'], 'stocks_product_truck_color_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE stocks DROP INDEX stocks_product_warehouse_color_unique');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE stocks DROP INDEX stocks_product_truck_color_unique');
        } catch (\Exception $e) {}

        Schema::table('stocks', function (Blueprint $table) {
            $table->unique(['product_id', 'warehouse_id'], 'stocks_product_warehouse_unique');
            $table->unique(['product_id', 'truck_id'], 'stocks_product_truck_unique');
            
            $table->dropConstrainedForeignId('color_id');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('color_id');
        });
    }
};
