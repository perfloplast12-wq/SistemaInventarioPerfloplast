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
        if (Schema::hasTable('trucks')) {
            Schema::table('trucks', function (Blueprint $table) {
                // Add driver_id (from 2026_03_13_170622)
                if (!Schema::hasColumn('trucks', 'driver_id')) {
                    $table->unsignedBigInteger('driver_id')->nullable()->after('plate');
                    $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
                }
                
                // Make driver_name nullable (from 2026_03_13_170839)
                if (Schema::hasColumn('trucks', 'driver_name')) {
                    $table->string('driver_name', 120)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Reversal logic
    }
};
