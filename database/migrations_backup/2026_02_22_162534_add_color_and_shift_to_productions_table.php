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
        Schema::table('productions', function (Blueprint $table) {
            if (!Schema::hasColumn('productions', 'color_id')) {
                $table->foreignId('color_id')->nullable()->constrained('colors')->restrictOnDelete();
            }
            if (!Schema::hasColumn('productions', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
            $table->dropColumn('color_id');
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });
    }
};
