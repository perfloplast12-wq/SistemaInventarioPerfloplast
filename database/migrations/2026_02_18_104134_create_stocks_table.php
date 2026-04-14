<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('truck_id')
                ->nullable()
                ->constrained('trucks')
                ->restrictOnDelete();

            $table->decimal('quantity', 14, 3)->default(0);

            $table->timestamps();

            // Un producto solo puede tener 1 registro por bodega / por camión
            $table->unique(['product_id', 'warehouse_id'], 'stocks_product_warehouse_unique');
            $table->unique(['product_id', 'truck_id'], 'stocks_product_truck_unique');

            $table->index('warehouse_id', 'stocks_warehouse_idx');
            $table->index('truck_id', 'stocks_truck_idx');
        });

        // CHECK: exactamente uno de warehouse_id o truck_id debe estar lleno
        DB::statement('
            ALTER TABLE stocks
            ADD CONSTRAINT stocks_one_location_check
            CHECK (
                (warehouse_id IS NOT NULL AND truck_id IS NULL)
                OR (warehouse_id IS NULL AND truck_id IS NOT NULL)
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
