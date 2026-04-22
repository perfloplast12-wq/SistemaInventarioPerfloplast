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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('presentation_sale_price', 12, 2)->nullable()->after('sale_price');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('unit_type')->default('base')->after('quantity'); // 'base' or 'presentation'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('presentation_sale_price');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_type');
        });
    }
};
