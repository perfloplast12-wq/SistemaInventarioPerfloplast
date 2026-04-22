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
        // 1. Order Items: Missing color_id
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'color_id')) {
                    $table->foreignId('color_id')->nullable()->after('product_id')->constrained('colors')->nullOnDelete();
                }
            });
        }

        // 2. Invoice Items: Sync with Model (product_code, product_name, total)
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                // Rename description to product_name if exists
                if (Schema::hasColumn('invoice_items', 'description') && !Schema::hasColumn('invoice_items', 'product_name')) {
                    $table->renameColumn('description', 'product_name');
                }
                // Rename subtotal to total if exists
                if (Schema::hasColumn('invoice_items', 'subtotal') && !Schema::hasColumn('invoice_items', 'total')) {
                    $table->renameColumn('subtotal', 'total');
                }
                // Add product_code
                if (!Schema::hasColumn('invoice_items', 'product_code')) {
                    $table->string('product_code')->nullable()->after('invoice_id');
                }
            });
        }

        // 3. Locations: Missing code and notes
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (!Schema::hasColumn('locations', 'code')) {
                    $table->string('code')->nullable()->unique()->after('name');
                }
                if (!Schema::hasColumn('locations', 'notes')) {
                    $table->text('notes')->nullable()->after('is_active');
                }
            });
        }

        // 4. Users: Missing created_by
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('remember_token')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 5. Order Returns: Missing dispatch_id, driver_id, truck_id, resolved_by
        if (Schema::hasTable('order_returns')) {
            Schema::table('order_returns', function (Blueprint $table) {
                if (!Schema::hasColumn('order_returns', 'dispatch_id')) {
                    $table->foreignId('dispatch_id')->nullable()->after('order_id')->constrained('dispatches')->nullOnDelete();
                }
                if (!Schema::hasColumn('order_returns', 'driver_id')) {
                    $table->foreignId('driver_id')->nullable()->after('color_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('order_returns', 'truck_id')) {
                    $table->foreignId('truck_id')->nullable()->after('driver_id')->constrained('trucks')->nullOnDelete();
                }
                if (!Schema::hasColumn('order_returns', 'resolved_by')) {
                    $table->foreignId('resolved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for sync as it fixes inconsistencies
    }
};
