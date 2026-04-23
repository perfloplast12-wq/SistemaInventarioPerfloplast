<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Esta migración unifica la creación del sistema de rastreo GPS de forma limpia.
     */
    public function up(): void
    {
        // Borramos la tabla si existe para asegurar un esquema limpio y sin errores de columnas faltantes
        Schema::dropIfExists('dispatch_locations');

        Schema::create('dispatch_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained()->cascadeOnDelete();
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('heading', 8, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Índice para mejorar el rendimiento de las consultas de ruta
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
