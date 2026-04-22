<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('type')->default('storage'); // storage, factory, retail
            $table->boolean('is_factory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable(); // Restored missing field
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'type']);
        });

        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('plate')->unique(); // Fixed: plate instead of plate_number to match Model
            $table->string('name')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('capacity_kg', 12, 2)->default(0);
            
            // Driver Assignment (Consolidated)
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('driver_name')->nullable(); // Restored missing field
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
        Schema::dropIfExists('warehouses');
    }
};
