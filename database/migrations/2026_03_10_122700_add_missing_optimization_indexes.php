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
        $this->addIndexSafe('dispatches', ['status']);
        $this->addIndexSafe('invoices', ['customer_nit']);
        $this->addIndexSafe('locations', ['type']);
        $this->addIndexSafe('order_returns', ['status']);
        $this->addIndexSafe('orders', ['customer_nit', 'phone']);
        $this->addIndexSafe('purchases', ['status']);
        $this->addIndexSafe('sales', ['customer_nit', 'receipt_number']);
        $this->addIndexSafe('suppliers', ['nit', 'phone', 'email']);
        $this->addIndexSafe('warehouses', ['type']);
    }

    /**
     * Helper to add indexes safely ignoring duplicates.
     */
    private function addIndexSafe(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            try {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->index($column);
                });
            } catch (\Exception $e) {
                // Ignore duplicate key errors
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexSafe('dispatches', ['status']);
        $this->dropIndexSafe('invoices', ['customer_nit']);
        $this->dropIndexSafe('locations', ['type']);
        $this->dropIndexSafe('order_returns', ['status']);
        $this->dropIndexSafe('orders', ['customer_nit', 'phone']);
        $this->dropIndexSafe('purchases', ['status']);
        $this->dropIndexSafe('sales', ['customer_nit', 'receipt_number']);
        $this->dropIndexSafe('suppliers', ['nit', 'phone', 'email']);
        $this->dropIndexSafe('warehouses', ['type']);
    }

    private function dropIndexSafe(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            try {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->dropIndex([$column]);
                });
            } catch (\Exception $e) {
                // Ignore errors on drop
            }
        }
    }
};
