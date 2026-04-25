<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Color;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportCatalog extends Command
{
    protected $signature = 'app:import-catalog';
    protected $description = 'Import product catalog data from MongoDB dump';

    public function handle()
    {
        $path = base_path('mongo_dump.json');
        if (!File::exists($path)) {
            $this->error("File not found: {$path}");
            return;
        }

        $data = json_decode(File::get($path), true);
        $products = $data['products'] ?? [];

        $this->info("Starting import of " . count($products) . " products...");

        // Asegurar que existan las unidades de medida
        $unit = DB::table('unit_of_measures')->where('name', 'Unidad')->first();
        if (!$unit) {
            $unitId = DB::table('unit_of_measures')->insertGetId([
                'name' => 'Unidad',
                'code' => 'UND',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $unitId = $unit->id;
        }

        foreach ($products as $pData) {
            $this->line("Importing: " . $pData['name']);

            // 1. Encontrar o crear producto
            $product = Product::updateOrCreate(
                ['name' => $pData['name']],
                [
                    'sku' => strtoupper(substr(md5($pData['name']), 0, 8)),
                    'unit_of_measure_id' => $unitId,
                    'presentation_unit_id' => $unitId,
                    'sale_price' => $pData['price'] ?? 0,
                    'catalog_description' => $pData['description'] ?? null,
                    'image_url' => $pData['image'] ?? null,
                    'mask_url' => $pData['maskImage'] ?? null,
                    'base_hue' => $pData['baseHue'] ?? 0,
                    'image_transform' => $pData['imageTransform'] ?? null,
                    'lumina' => $pData['lumina'] ?? null,
                    'is_active' => true,
                    'show_in_catalog' => true,
                ]
            );

            // 2. Procesar Colores
            if (isset($pData['colors']) && is_array($pData['colors'])) {
                $colors = $pData['colors'] ?? [];
                    $colorIdsWithPivot = [];
                    foreach ($colors as $index => $cData) {
                        $hex = $cData['hex'] ?? null;
                        // Encontrar o crear el color base (solo por nombre para evitar duplicados)
                        $color = Color::updateOrCreate(
                            ['name' => $cData['name']],
                            [
                                'code' => strtoupper(\Illuminate\Support\Str::slug($cData['name'])),
                                'is_active' => true,
                            ]
                        );
                        
                        $colorIdsWithPivot[$color->id] = [
                            'hex_code' => $hex, // Guardamos el tono específico aquí
                            'image_url' => $cData['image'] ?? null,
                            'brightness' => isset($cData['lumina']['brightness']) ? (int)($cData['lumina']['brightness'] * 100) : 100,
                            'contrast' => isset($cData['lumina']['contrast']) ? (int)($cData['lumina']['contrast'] * 100) : 100,
                        ];

                        if ($index === 0) {
                            $product->color_id = $color->id;
                        }
                    }
                    $product->colors()->sync($colorIdsWithPivot);
                $product->save();
            }

            // 3. Procesar Variantes (Types)
            if (isset($pData['types']) && is_array($pData['types'])) {
                foreach ($pData['types'] as $vData) {
                    // Crear también como producto real para el inventario del ERP
                    $variantProduct = Product::updateOrCreate(
                        ['name' => $vData['name']],
                        [
                            'sku' => strtoupper(substr(md5($vData['name']), 0, 8)),
                            'unit_of_measure_id' => $unitId,
                            'presentation_unit_id' => $unitId,
                            'sale_price' => $vData['price'] ?? $product->sale_price,
                            'catalog_description' => $vData['description'] ?? $product->catalog_description,
                            'image_url' => $vData['image'] ?? $product->image_url,
                            'mask_url' => $vData['maskImage'] ?? $product->mask_url,
                            'image_transform' => $vData['imageTransform'] ?? $product->image_transform,
                            'lumina' => $vData['lumina'] ?? $product->lumina,
                            'is_active' => true,
                            'show_in_catalog' => false, // No mostrar como tarjeta principal, solo dentro del modelo
                        ]
                    );

                    ProductVariant::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'name' => $vData['name']
                        ],
                        [
                            'price' => $vData['price'] ?? $product->sale_price,
                            'description' => $vData['description'] ?? null,
                            'image_url' => $vData['image'] ?? null,
                            'mask_url' => $vData['maskImage'] ?? null,
                            'image_transform' => $vData['imageTransform'] ?? null,
                            'lumina' => $vData['lumina'] ?? null,
                        ]
                    );
                }
            }
        }

        // 4. Settings
        if (isset($data['settings'][0])) {
            $sData = $data['settings'][0];
            DB::table('catalog_settings')->updateOrInsert(
                ['key' => 'background_scene_url'],
                ['value' => $sData['sceneBackground'] ?? null]
            );
        }

        $this->info("Import completed successfully!");
    }
}
