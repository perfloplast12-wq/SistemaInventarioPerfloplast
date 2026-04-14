<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('discount_type')->default('none')->after('total'); // none|percent|fixed
            $table->decimal('discount_value', 14, 3)->default(0)->after('discount_type');
            $table->decimal('discount_amount', 14, 3)->default(0)->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']);
        });
    }
};
