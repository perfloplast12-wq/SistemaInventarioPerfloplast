<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            
            // Materia prima consumida
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_items');
    }
};
