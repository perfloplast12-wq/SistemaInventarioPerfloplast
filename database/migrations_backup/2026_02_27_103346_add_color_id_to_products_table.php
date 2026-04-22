<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'color_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('color_id')->nullable()->after('unit_of_measure_id')->constrained('colors')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('color_id');
        });
    }
};
