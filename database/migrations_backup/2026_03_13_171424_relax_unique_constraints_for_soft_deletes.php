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
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique('warehouses_code_unique');
            $table->index('code');
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->dropUnique('trucks_plate_unique');
            $table->index('plate');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_invoice_number_unique');
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->unique('code');
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->dropIndex(['plate']);
            $table->unique('plate');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_number']);
            $table->unique('invoice_number');
        });
    }
};
