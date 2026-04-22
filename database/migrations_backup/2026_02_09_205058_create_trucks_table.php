<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();

            $table->string('plate', 20)->unique();     // placa
            $table->string('brand', 60)->nullable();   // marca
            $table->string('model', 60)->nullable();   // modelo opcional

            $table->string('driver_name', 120);        // piloto (por ahora texto)

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
