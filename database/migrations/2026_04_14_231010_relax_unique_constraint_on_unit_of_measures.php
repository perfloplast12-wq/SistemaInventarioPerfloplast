<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the strict unique constraint on name so soft-deleted
     * records don't block the creation of new records with the
     * same name.
     */
    public function up(): void
    {
        // Check if the unique index still exists before trying to drop it
        $indexes = \DB::select("SHOW INDEX FROM unit_of_measures WHERE Key_name = 'unit_of_measures_name_unique'");
        
        if (!empty($indexes)) {
            Schema::table('unit_of_measures', function (Blueprint $table) {
                $table->dropUnique('unit_of_measures_name_unique');
            });
        }

        // Add normal index if it doesn't exist
        $normalIndex = \DB::select("SHOW INDEX FROM unit_of_measures WHERE Key_name = 'unit_of_measures_name_index'");
        if (empty($normalIndex)) {
            Schema::table('unit_of_measures', function (Blueprint $table) {
                $table->index('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('unit_of_measures', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->unique('name');
        });
    }
};
