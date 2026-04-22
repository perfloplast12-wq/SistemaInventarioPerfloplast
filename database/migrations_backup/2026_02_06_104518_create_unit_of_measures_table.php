<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();

            $table->string('name', 80);          // Unidad, Paquete, Kg...
            $table->string('abbreviation', 20)->nullable(); // u, pq, kg, etc
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
