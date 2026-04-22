<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('production_number')->unique(); // P-000001
            $table->dateTime('production_date');
            
            // Producto terminado a producir
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            
            // Bodega donde entra el stock (usualmente fábrica)
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            
            $table->decimal('quantity', 14, 3);
            $table->string('status')->default('draft'); // draft, confirmed, cancelled
            $table->text('note')->nullable();
            
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
