<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, json, image
            $table->timestamps();
        });

        // Insertar ajustes por defecto
        DB::table('catalog_settings')->insert([
            ['key' => 'background_scene_url', 'value' => null, 'type' => 'image', 'created_at' => now()],
            ['key' => 'catalog_is_active', 'value' => 'true', 'type' => 'boolean', 'created_at' => now()],
            ['key' => 'last_sync_date', 'value' => now(), 'type' => 'datetime', 'created_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_settings');
    }
};
