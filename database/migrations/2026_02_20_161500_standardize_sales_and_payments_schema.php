<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Adjust Sale Payments
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

        // Standardize inventory movements precision using raw SQL to avoid PDO/DBAL issues with change()
        DB::statement('UPDATE inventory_movements SET quantity = 0 WHERE quantity IS NULL');
        DB::statement('ALTER TABLE inventory_movements MODIFY quantity DECIMAL(14,3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE inventory_movements MODIFY unit_cost DECIMAL(14,3) NULL');

        // Standardize products precision
        DB::statement('UPDATE products SET sale_price = 0 WHERE sale_price IS NULL');
        DB::statement('UPDATE products SET cost_price = 0 WHERE cost_price IS NULL');
        DB::statement('ALTER TABLE products MODIFY sale_price DECIMAL(14,3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE products MODIFY cost_price DECIMAL(14,3) NOT NULL DEFAULT 0');
        
        if (Schema::hasColumn('products', 'purchase_cost')) {
            DB::statement('ALTER TABLE products MODIFY purchase_cost DECIMAL(14,3) NULL');
        }
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_date', 'notes']);
        });
    }
};
