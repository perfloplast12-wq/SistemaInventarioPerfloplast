<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('customer_name');
            $table->index('sale_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('customer_name');
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['customer_name']);
            $table->dropIndex(['sale_number']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_name']);
            $table->dropIndex(['order_number']);
        });
    }
};
