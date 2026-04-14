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
        Schema::create('injection_reports', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('turno_horario');
            $table->string('nombre_empleado');
            $table->string('maquina');
            $table->string('producto');
            $table->string('producto_por_color')->nullable();
            $table->integer('total')->default(0);
            $table->integer('rechazo')->default(0);
            $table->integer('sacos_usados')->default(0);
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('injection_reports');
    }
};
