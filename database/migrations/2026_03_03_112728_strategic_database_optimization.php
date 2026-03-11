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
        $this->addIndexSafe('products', ['sku', 'type']);
        $this->addIndexSafe('orders', ['status', 'created_at', 'customer_name']);
        $this->addIndexSafe('dispatches', ['dispatch_date']);
        $this->addIndexSafe('sales', ['created_at']);
        $this->addIndexSafe('productions', ['created_at']);
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
        $this->dropIndexSafe('products', ['sku', 'type']);
        $this->dropIndexSafe('orders', ['status', 'created_at', 'customer_name']);
        $this->dropIndexSafe('dispatches', ['dispatch_date']);
        $this->dropIndexSafe('sales', ['created_at']);
        $this->dropIndexSafe('productions', ['created_at']);
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
