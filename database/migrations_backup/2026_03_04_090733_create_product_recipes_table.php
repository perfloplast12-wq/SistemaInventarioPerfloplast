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
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('raw_material_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 12, 4); // Cantidad de materia prima necesaria
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
