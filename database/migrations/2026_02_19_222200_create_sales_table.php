<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique(); // V-000001
            $table->dateTime('sale_date');
            $table->string('status')->default('draft'); // draft, confirmed, cancelled
            
            // Origen (XOR)
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('from_truck_id')->nullable()->constrained('trucks')->restrictOnDelete();
            
            $table->string('customer_name')->nullable();
            $table->text('note')->nullable();
            $table->decimal('total', 14, 3)->nullable();
            
            // Recibo
            $table->string('receipt_number')->nullable();
            $table->string('receipt_path')->nullable();
            
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
