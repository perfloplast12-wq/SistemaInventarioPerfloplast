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
        // 1. Structural Cleanup
        Schema::dropIfExists('inventory_movement_items');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'color')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }

        // 2. Simplified Index Creation (Removing the Doctrine guards for now)
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['sale_date', 'status']);
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->index(['production_date', 'status']);
        });

        Schema::table('dispatches', function (Blueprint $table) {
            $table->index(['dispatch_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                 $table->dropIndex(['sale_date', 'status']);
            });
        }
        if (Schema::hasTable('productions')) {
            Schema::table('productions', function (Blueprint $table) {
                 $table->dropIndex(['production_date', 'status']);
            });
        }
        if (Schema::hasTable('dispatches')) {
            Schema::table('dispatches', function (Blueprint $table) {
                 $table->dropIndex(['dispatch_date', 'status']);
            });
        }
    }
};
