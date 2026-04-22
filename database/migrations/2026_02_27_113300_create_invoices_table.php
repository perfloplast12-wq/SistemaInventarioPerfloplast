<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->dateTime('invoice_date');
            
            // Customer
            $table->string('customer_name');
            $table->string('customer_nit');
            $table->string('customer_address')->nullable();
            
            // Link to Order/Sale
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            
            // Financials (Restored missing fields from Model)
            $table->string('payment_method')->nullable();
            $table->string('sale_type')->nullable(); // factory, retail, etc.
            
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('change_amount', 14, 2)->default(0);
            
            $table->string('status')->default('active'); // active, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('customer_nit');
            $table->index('invoice_date');
            $table->index('status');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
