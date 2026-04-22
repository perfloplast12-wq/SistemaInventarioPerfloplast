<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('name')
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->foreignId('truck_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('trucks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('truck_id');
        });
    }
};
