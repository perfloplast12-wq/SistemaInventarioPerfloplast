<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
   public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            // in, out, adjust, transfer
            $table->string('type', 20);

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // ubicaciones opcionales
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->foreignId('from_truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            $table->foreignId('to_truck_id')->nullable()->constrained('trucks')->nullOnDelete();

            $table->decimal('quantity', 12, 2)->default(0);

            // costo unitario SOLO para entradas (opcional)
            $table->decimal('unit_cost', 12, 2)->nullable();

            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
