<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 50)->unique();
            $table->dateTime('sale_date');
            
            // Customer Info
            $table->string('customer_name');
            $table->string('customer_nit')->nullable()->default('C/F');
            
            // Locations (Consolidated from Model names)
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('from_truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            
            // Transaction Details
            $table->string('origin_type')->default('direct'); 
            $table->string('status')->default('completed'); 
            $table->string('receipt_number')->nullable();
            $table->string('receipt_path')->nullable();
            
            // Financials (Consolidated from Model names)
            $table->decimal('total', 14, 3)->default(0);
            $table->string('discount_type')->nullable(); // fixed, percentage
            $table->decimal('discount_value', 14, 3)->default(0);
            $table->decimal('discount_amount', 14, 3)->default(0);
            
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Optimization Indices
            $table->index(['sale_date', 'status']);
            $table->index(['customer_name', 'customer_nit']);
            $table->index('origin_type');
            $table->index('created_by');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 3);
            $table->decimal('discount_amount', 14, 3)->default(0);
            $table->decimal('subtotal', 14, 3);
            $table->decimal('total', 14, 3);
            
            $table->timestamps();

            $table->index(['sale_id', 'product_id', 'color_id']);
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method'); 
            $table->decimal('amount', 14, 3);
            $table->dateTime('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
