<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Quién hizo la acción
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Qué pasó
            $table->string('event', 40);          // created, updated, deleted, login, logout, roles_sync, toggled_active, etc.
            $table->string('module', 60);         // users, products, inventory, sales...
            $table->string('auditable_type', 150)->nullable(); // App\Models\User, App\Models\Product...
            $table->unsignedBigInteger('auditable_id')->nullable();

            // Datos antes/después
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changes')->nullable(); // diff calculado (solo lo que cambió)

            // Contexto (trazabilidad)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('method', 10)->nullable();

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['module', 'event']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
