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
        // Fuerza a DigitalOcean a actualizar la base de datos con los nuevos permisos
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'PermissionsSeeder',
            '--force' => true,
        ]);
        
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'RolesSeeder',
            '--force' => true,
        ]);
        
        // Limpiamos la caché de Spatie para que tome efecto inmediatamente
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nada que revertir
    }
};
