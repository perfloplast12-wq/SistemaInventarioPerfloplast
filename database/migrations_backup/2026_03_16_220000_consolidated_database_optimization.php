<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- 1. PREVIOUS OPTIMIZATIONS (Consolidated) ---

        // Sales, Productions, inventory_movements basic indexes
        $this->addIndexesSafe('sales', ['sale_date', 'status', 'customer_name', 'created_at', 'customer_nit', 'receipt_number', 'origin_type']);
        $this->addIndexesSafe('productions', ['production_date', 'status', 'created_at', 'color_id', 'shift_id']);
        $this->addIndexesSafe('inventory_movements', ['type', 'motive', 'warehouse_id', 'truck_id', 'color_id']);
        $this->addIndexesSafe('warehouses', ['is_active', 'type']);
        $this->addIndexesSafe('trucks', ['is_active']);
        
        // Products and Orders
        $this->addIndexesSafe('products', ['sku', 'type', 'is_active', 'unit_of_measure_id']);
        $this->addIndexesSafe('orders', ['status', 'created_at', 'customer_name', 'customer_nit', 'phone', 'created_by', 'dispatch_id']);
        
        // Logistics and Suppliers
        $this->addIndexesSafe('dispatches', ['dispatch_date', 'status']);
        $this->addIndexesSafe('invoices', ['customer_nit']);
        $this->addIndexesSafe('locations', ['type']);
        $this->addIndexesSafe('order_returns', ['status', 'order_id', 'product_id', 'color_id']);
        $this->addIndexesSafe('purchases', ['status']);
        $this->addIndexesSafe('suppliers', ['nit', 'phone', 'email']);

        // --- 2. ITEM-LEVEL OPTIMIZATIONS ---
        $this->addIndexesSafe('sale_items', ['sale_id', 'product_id', 'color_id']);
        $this->addIndexesSafe('order_items', ['order_id', 'product_id', 'color_id']);
        $this->addIndexesSafe('production_items', ['production_id', 'product_id']);
        $this->addIndexesSafe('dispatch_items', ['dispatch_id', 'product_id', 'color_id']);
        $this->addIndexesSafe('purchase_items', ['purchase_id', 'product_id']);
        $this->addIndexesSafe('invoice_items', ['invoice_id']);
        $this->addIndexesSafe('inventory_movement_items', ['product_id']);

        // Composite/Special indexes
        try {
            Schema::table('inventory_movements', function (Blueprint $table) {
                // Only if not already present
                $table->index(['created_at', 'type'], 'inv_mov_created_at_type_idx');
            });
        } catch (\Exception $e) {}
    }

    /**
     * Helper to add multiple indexes safely.
     */
    private function addIndexesSafe(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) return;

        foreach ($columns as $column) {
            try {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, function (Blueprint $tableObj) use ($column) {
                        $tableObj->index($column);
                    });
                }
            } catch (\Exception $e) {
                // Ignore if index already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Generally we don't drop indexes in down for consolidated performance migrations
        // but if strictly needed, we would list them all here.
    }
};
