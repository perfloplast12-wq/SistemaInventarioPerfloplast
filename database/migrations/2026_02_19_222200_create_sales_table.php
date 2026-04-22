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
            $table->string('customer_nit')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('suppliers')->nullOnDelete(); // Assuming it might link to a master list
            
            // Transaction Details
            $table->string('origin_type')->default('direct'); // pos, order, dispatch
            $table->string('status')->default('completed'); // pending, completed, cancelled
            $table->string('receipt_number')->nullable();
            
            // Financials
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            
            // Payment Status
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('payment_status')->default('paid'); // unpaid, partial, paid
            
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // Who made the sale

            $table->timestamps();
            $table->softDeletes(); // Consolidated

            // Optimization Indices
            $table->index(['sale_date', 'status']);
            $table->index(['customer_name', 'customer_nit']);
            $table->index('origin_type');
            $table->index('user_id');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('total', 14, 2);
            
            $table->timestamps();

            $table->index(['sale_id', 'product_id', 'color_id']);
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method'); // cash, card, transfer
            $table->decimal('amount', 14, 2);
            $table->dateTime('payment_date');
            $table->string('reference_number')->nullable();
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
