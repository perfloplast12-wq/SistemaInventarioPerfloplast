<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('production_number')->unique();
            $table->dateTime('production_date');
            
            // Context
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            
            // Main Product
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, draft
            $table->decimal('quantity', 14, 3)->default(0);
            
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index(['production_date', 'status']);
            $table->index(['shift_id', 'color_id']);
        });

        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('waste_quantity', 14, 3)->default(0);
            $table->timestamps();

            $table->index(['production_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('productions');
    }
};
