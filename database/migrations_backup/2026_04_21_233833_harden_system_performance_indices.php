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
        // Productions optimizations
        Schema::table('productions', function (Blueprint $table) {
            $this->addIndexSafe($table, 'productions', 'shift_id');
            $this->addIndexSafe($table, 'productions', 'color_id');
            $this->addIndexSafe($table, 'productions', 'product_id');
        });

        // Sales optimizations
        Schema::table('sales', function (Blueprint $table) {
            $this->addIndexSafe($table, 'sales', 'customer_id');
            $this->addIndexSafe($table, 'sales', 'user_id');
        });

        // Sale Items (Top Selling Products)
        Schema::table('sale_items', function (Blueprint $table) {
            $this->addIndexSafe($table, 'sale_items', 'product_id');
            $this->addIndexSafe($table, 'sale_items', 'sale_id');
        });

        // Inventory Movements
        Schema::table('inventory_movements', function (Blueprint $table) {
            $this->addIndexSafe($table, 'inventory_movements', 'product_id');
            $this->addIndexSafe($table, 'inventory_movements', 'warehouse_id');
            $this->addIndexSafe($table, 'inventory_movements', 'color_id');
        });

        // Orders
        Schema::table('orders', function (Blueprint $table) {
            $this->addIndexSafe($table, 'orders', 'customer_id');
        });
    }

    private function addIndexSafe(Blueprint $table, string $tableName, string $columnName): void
    {
        $indexName = "{$tableName}_{$columnName}_index";
        $conn = Schema::getConnection();
        $results = $conn->select("SHOW INDEX FROM {$tableName} WHERE Key_name = '{$indexName}'");

        if (count($results) === 0) {
            $table->index($columnName, $indexName);
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
