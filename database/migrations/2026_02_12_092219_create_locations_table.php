<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('shelf'); // shelf, bin, section
            $table->text('notes')->nullable();
            
            // Parent Infrastructure (Consolidated)
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'truck_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
