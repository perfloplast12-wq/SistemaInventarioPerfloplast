<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_number')->unique();
            $table->foreignId('truck_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            
            // Personnel (Consolidated)
            $table->string('driver_name');
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete(); // Reference to user record
            
            $table->string('route')->nullable();
            $table->dateTime('dispatch_date');
            $table->string('status')->default('pending'); // pending, in_progress, completed, delivered, cancelled
            
            $table->decimal('total_value', 14, 2)->default(0);
            $table->integer('total_products')->default(0);
            $table->integer('product_types')->default(0);
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes(); // Consolidated

            // Indices
            $table->index(['dispatch_date', 'status']);
            $table->index('truck_id');
        });

        Schema::create('dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();

            $table->index(['dispatch_id', 'product_id', 'color_id']);
        });

        Schema::create('dispatch_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained()->cascadeOnDelete();
            $table->string('location_name');
            $table->string('contact_person')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // FK for orders link
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'dispatch_id')) {
                    $table->unsignedBigInteger('dispatch_id')->nullable();
                }
                $table->foreign('dispatch_id')->references('id')->on('dispatches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_locations');
        Schema::dropIfExists('dispatch_items');
        Schema::dropIfExists('dispatches');
    }
};
