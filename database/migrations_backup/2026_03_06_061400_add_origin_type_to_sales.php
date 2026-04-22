<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('origin_type')->nullable()->after('from_truck_id');
        });

        // Retroactively tag existing Quick Sales (those with from_warehouse_id pointing to a factory)
        $factoryIds = DB::table('warehouses')->where('is_factory', true)->pluck('id');

        if ($factoryIds->isNotEmpty()) {
            // Sales from factory warehouses that have NO truck = Quick Sales
            DB::table('sales')
                ->whereIn('from_warehouse_id', $factoryIds)
                ->whereNull('from_truck_id')
                ->update(['origin_type' => 'warehouse']);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('origin_type');
        });
    }
};
