<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            // in, out, adjust, transfer, production, sale, return
            $table->string('type', 20);
            $table->string('motive', 50)->nullable(); // purchase, production, sale, damage, adjustment, return

            // Product & Variant
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();

            // Location (Consolidated)
            // Original fields for transfers
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('from_truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            $table->foreignId('to_truck_id')->nullable()->constrained('trucks')->nullOnDelete();

            // Simplified direct references added later
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();

            // Quantities & Accounting
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 2)->nullable();

            // Source Tracking
            $table->string('source_type')->nullable(); // production, sale, order, return
            $table->unsignedBigInteger('source_id')->nullable();
            
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Consolidated

            // Optimization Indices
            $table->index(['type', 'motive']);
            $table->index(['source_type', 'source_id']);
            $table->index(['created_at', 'type']);
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('truck_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
