<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movement_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_movement_id')
                ->constrained('inventory_movements')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->decimal('quantity', 12, 3); // permite kg 1.250, etc.
            $table->decimal('unit_cost', 12, 2)->default(0); // costo para valorización

            $table->timestamps();

            $table->index(['inventory_movement_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movement_items');
    }
};
