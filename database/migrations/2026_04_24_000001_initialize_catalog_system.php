<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Campos adicionales en Productos
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('description');
            $table->string('mask_url')->nullable()->after('image_url');
            $table->text('catalog_description')->nullable()->after('description');
            $table->decimal('base_hue', 8, 2)->default(0)->after('mask_url');
            $table->json('image_transform')->nullable()->after('base_hue');
            $table->json('lumina')->nullable()->after('image_transform');
            $table->boolean('show_in_catalog')->default(true)->after('is_active');
        });

        // 2. Campos adicionales en Colores
        Schema::table('colors', function (Blueprint $table) {
            $table->string('hex_code', 7)->nullable()->after('name');
        });

        // 3. Tabla de Modelos/Variantes
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->string('mask_url')->nullable();
            $table->json('image_transform')->nullable();
            $table->json('lumina')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Tabla Pivote Producto-Color (Con soporte para texturas e iluminación específica)
        Schema::create('color_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('color_id')->constrained()->onDelete('cascade');
            $table->string('image_url')->nullable();
            $table->integer('brightness')->default(100);
            $table->integer('contrast')->default(100);
            $table->timestamps();
        });

        // 5. Ajustes del Catálogo
        Schema::create('catalog_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_settings');
        Schema::dropIfExists('color_product');
        Schema::dropIfExists('product_variants');
        
        Schema::table('colors', function (Blueprint $table) {
            $table->dropColumn(['hex_code']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'image_url', 'mask_url', 'catalog_description', 
                'base_hue', 'image_transform', 'lumina', 'show_in_catalog'
            ]);
        });
    }
};
