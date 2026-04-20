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
            $table->index('status');
            $table->index('sale_date');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('order_date');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->index('status');
            $table->index('production_date');
        });

        Schema::table('dispatches', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['sale_date']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['order_date']);
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['production_date']);
        });

        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
        });
    }
};
