<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dispatch_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained()->cascadeOnDelete();
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->decimal('speed', 5, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['dispatch_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_locations');
    }
};
