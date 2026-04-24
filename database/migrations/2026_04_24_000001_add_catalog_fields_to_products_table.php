<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Campos para el catálogo
            $table->string('image_url')->nullable()->after('description');
            $table->string('mask_url')->nullable()->after('image_url');
            $table->text('catalog_description')->nullable()->after('description');
            $table->decimal('base_hue', 8, 2)->default(0)->after('mask_url');
            
            // Ajustes de transformación (Escala, X, Y) guardados como JSON
            $table->json('image_transform')->nullable()->after('base_hue');
            
            // Estado en el catálogo
            $table->boolean('show_in_catalog')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'image_url',
                'mask_url',
                'catalog_description',
                'base_hue',
                'image_transform',
                'show_in_catalog'
            ]);
        });
    }
};
