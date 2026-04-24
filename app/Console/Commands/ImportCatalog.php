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

        foreach ($products as $pData) {
            $this->line("Importing: " . $pData['name']);

            // 1. Encontrar o crear producto
            $product = Product::updateOrCreate(
                ['name' => $pData['name']],
                [
                    'sku' => strtoupper(substr(md5($pData['name']), 0, 8)),
                    'unit_of_measure_id' => 3, // Unidad
                    'presentation_unit_id' => 3, // Unidad
                    'sale_price' => $pData['price'] ?? 0,
                    'catalog_description' => $pData['description'] ?? null,
                    'image_url' => $pData['image'] ?? null,
                    'mask_url' => $pData['maskImage'] ?? null,
                    'base_hue' => $pData['baseHue'] ?? 0,
                    'image_transform' => $pData['imageTransform'] ?? null,
                    'is_active' => true,
                    'show_in_catalog' => true,
                ]
            );

            // 2. Procesar Colores
            if (isset($pData['colors']) && is_array($pData['colors'])) {
                $colorIds = [];
                foreach ($pData['colors'] as $cData) {
                    $color = Color::updateOrCreate(
                        ['name' => $cData['name']],
                        [
                            'code' => strtoupper(\Illuminate\Support\Str::slug($cData['name'])),
                            'hex_code' => $cData['hex'] ?? null,
                            'brightness' => isset($cData['lumina']['brightness']) ? (int)($cData['lumina']['brightness'] * 100) : 100,
                            'contrast' => isset($cData['lumina']['contrast']) ? (int)($cData['lumina']['contrast'] * 100) : 100,
                        ]
                    );
                    $colorIds[] = $color->id;
                }
                // Nota: Asumiendo que existe una relación o campo color_id
                // Si quieres múltiples colores, necesitarías una tabla pivote.
                // Por ahora asignamos el primero si el modelo Product solo tiene color_id
                if (!empty($colorIds)) {
                    $product->update(['color_id' => $colorIds[0]]);
                }
            }

            // 3. Procesar Variantes (Types)
            if (isset($pData['types']) && is_array($pData['types'])) {
                foreach ($pData['types'] as $vData) {
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
