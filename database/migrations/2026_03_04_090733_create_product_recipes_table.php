<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('products')->restrictOnDelete();
            
            // Proporción o cantidad necesaria
            $table->decimal('quantity_needed', 14, 4);
            
            $table->timestamps();
            
            $table->index(['finished_product_id', 'raw_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
