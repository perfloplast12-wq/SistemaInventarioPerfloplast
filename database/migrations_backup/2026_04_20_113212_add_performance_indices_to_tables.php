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
        $this->addIndexSafe('sales', 'status');
        $this->addIndexSafe('sales', 'sale_date');
        $this->addIndexSafe('orders', 'status');
        $this->addIndexSafe('orders', 'order_date');
        $this->addIndexSafe('productions', 'status');
        $this->addIndexSafe('productions', 'production_date');
        $this->addIndexSafe('dispatches', 'status');
        $this->addIndexSafe('inventory_movements', 'type');
        $this->addIndexSafe('inventory_movements', 'created_at');
    }

    private function addIndexSafe(string $tableName, string $columnName): void
    {
        $indexName = "{$tableName}_{$columnName}_index";
        $conn = \Illuminate\Support\Facades\Schema::getConnection();
        $results = $conn->select("SHOW INDEX FROM {$tableName} WHERE Key_name = '{$indexName}'");

        if (count($results) === 0) {
            \Illuminate\Support\Facades\Schema::table($tableName, function (\Illuminate\Database\Schema\Blueprint $table) use ($columnName) {
                $table->index($columnName);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
