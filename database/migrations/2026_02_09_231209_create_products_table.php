<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('sku', 60)->nullable()->unique(); // opcional
            $table->enum('type', ['raw_material', 'finished_product']); // MP / PT

            $table->foreignId('unit_of_measure_id')
                ->constrained('unit_of_measures')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->boolean('is_active')->default(true);

            // Opcionales (si quieres desde ya)
            $table->text('description')->nullable();
            $table->string('color')->nullable(); // si luego lo manejas por variación/código

            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('unit_of_measure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
