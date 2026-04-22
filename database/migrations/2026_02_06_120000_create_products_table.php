<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Core Identity
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Classification
            $table->string('type')->default('finished'); // raw_material, finished
            $table->foreignId('unit_of_measure_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            
            // Pricing & Costs (Consolidated)
            $table->decimal('purchase_cost', 14, 2)->default(0);
            $table->decimal('wholesale_price', 14, 2)->default(0);
            $table->decimal('retail_price', 14, 2)->default(0);
            $table->decimal('distributor_price', 14, 2)->default(0);
            
            // Inventory Rules
            $table->decimal('minimum_stock', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            
            // Presentation (Consolidated)
            $table->string('presentation_type')->nullable(); // Bolsa, Saco, Granel
            $table->decimal('presentation_quantity', 14, 2)->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Consolidated

            // Optimization Indices
            $table->index(['type', 'is_active']);
            $table->index('unit_of_measure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
