<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_number')->unique();
            $table->foreignId('truck_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('driver_name');
            $table->string('route');
            $table->dateTime('dispatch_date');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'delivered'])->default('pending');
            $table->decimal('total_value', 14, 3)->default(0);
            $table->integer('total_products')->default(0);
            $table->integer('product_types')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 3);
            $table->decimal('subtotal', 12, 3);
            $table->timestamps();
        });

        // Add FK for orders.dispatch_id now that dispatches exists
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('dispatch_id')->references('id')->on('dispatches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_items');
        Schema::dropIfExists('dispatches');
    }
};
