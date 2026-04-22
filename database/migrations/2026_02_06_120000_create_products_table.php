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
            $table->string('type')->default('finished');
            $table->foreignId('unit_of_measure_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            
            // Pricing & Costs (Restored original names from Model)
            $table->decimal('purchase_cost', 14, 3)->default(0);
            $table->decimal('cost_price', 14, 3)->default(0);
            $table->decimal('sale_price', 14, 3)->default(0);
            
            // Tiered Pricing (Added in history)
            $table->decimal('wholesale_price', 14, 3)->default(0);
            $table->decimal('retail_price', 14, 3)->default(0);
            $table->decimal('distributor_price', 14, 3)->default(0);
            
            // Presentation (Restored original names from Model)
            $table->foreignId('presentation_unit_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('units_per_presentation', 14, 4)->default(1);
            $table->decimal('presentation_sale_price', 14, 3)->default(0);
            
            // Inventory Rules
            $table->decimal('minimum_stock', 14, 3)->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();

            // Optimization Indices
            $table->index(['type', 'is_active']);
            $table->index('unit_of_measure_id');
            $table->index('color_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
