<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Unique index for sale_number (from 2026_02_20_150336)
        if (Schema::hasTable('sales')) {
            DB::statement('ALTER TABLE sales MODIFY sale_number VARCHAR(50) NOT NULL');
            
            $indexExists = DB::select("SHOW INDEX FROM sales WHERE Key_name = 'sales_sale_number_unique'");
            if (empty($indexExists)) {
                DB::statement('ALTER TABLE sales ADD UNIQUE INDEX sales_sale_number_unique (sale_number)');
            }
        }

        // 2. Adjust Sale Payments (from 2026_02_20_161500)
        if (Schema::hasTable('sale_payments')) {
            Schema::table('sale_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_payments', 'payment_date')) {
                    $table->dateTime('payment_date')->nullable()->after('amount');
                }
                if (!Schema::hasColumn('sale_payments', 'notes')) {
                    $table->text('notes')->nullable()->after('payment_date');
                }
            });
        }

        // 3. Standardize inventory movements precision
        if (Schema::hasTable('inventory_movements')) {
            DB::statement('UPDATE inventory_movements SET quantity = 0 WHERE quantity IS NULL');
            DB::statement('ALTER TABLE inventory_movements MODIFY quantity DECIMAL(14,3) NOT NULL DEFAULT 0');
            if (Schema::hasColumn('inventory_movements', 'unit_cost')) {
                DB::statement('ALTER TABLE inventory_movements MODIFY unit_cost DECIMAL(14,3) NULL');
            }
        }

        // 4. Standardize products precision
        if (Schema::hasTable('products')) {
            DB::statement('UPDATE products SET sale_price = 0 WHERE sale_price IS NULL');
            DB::statement('UPDATE products SET cost_price = 0 WHERE cost_price IS NULL');
            DB::statement('ALTER TABLE products MODIFY sale_price DECIMAL(14,3) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE products MODIFY cost_price DECIMAL(14,3) NOT NULL DEFAULT 0');
            
            if (Schema::hasColumn('products', 'purchase_cost')) {
                DB::statement('ALTER TABLE products MODIFY purchase_cost DECIMAL(14,3) NULL');
            }
        }
    }

    public function down(): void
    {
        // Reversal logic if needed
    }
};
