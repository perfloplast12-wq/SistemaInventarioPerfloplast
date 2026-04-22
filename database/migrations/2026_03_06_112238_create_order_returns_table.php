<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            
            $table->decimal('quantity', 14, 3);
            $table->string('reason');
            $table->string('status')->default('pending'); // pending, processed, rejected
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id', 'product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_returns');
    }
};
