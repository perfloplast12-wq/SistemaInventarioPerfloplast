<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete()->after('product_id');
        });

        Schema::table('dispatch_items', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete()->after('product_id');
        });

        Schema::table('order_returns', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
            $table->dropColumn('color_id');
        });

        Schema::table('dispatch_items', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
            $table->dropColumn('color_id');
        });

        Schema::table('order_returns', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
            $table->dropColumn('color_id');
        });
    }
};
