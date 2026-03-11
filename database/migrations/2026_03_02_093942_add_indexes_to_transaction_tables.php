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
        Schema::table('sales', function (Blueprint $table) {
            $table->index('sale_date');
            $table->index('status');
            $table->index('customer_name');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->index('production_date');
            $table->index('status');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index('type');
            $table->index(['created_at', 'type']);
            $table->index('motive');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sale_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['customer_name']);
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex(['production_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at', 'type']);
            $table->dropIndex(['motive']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
