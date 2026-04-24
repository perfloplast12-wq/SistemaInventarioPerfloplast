<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Color;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function index()
    {
        // Traer productos activos que deben mostrarse en el catálogo
        $products = Product::where('show_in_catalog', true)
            ->where('is_active', true)
            ->get();

        $formattedProducts = $products->map(function ($product) {
            // Formatear colores específicos del producto
            $colors = $product->colors->map(function ($color) {
                return [
                    'id' => $color->id,
                    'name' => $color->name,
                    'hex' => $color->hex_code,
                    'lumina' => [
                        'brightness' => $color->brightness / 100,
                        'contrast' => $color->contrast / 100,
                    ]
                ];
            });

            // Formatear variantes
            $types = $product->variants->map(function ($variant) {
                return [
                    'name' => $variant->name,
                    'price' => number_format((float)$variant->price, 2, '.', ''),
                    'image' => $variant->image_url,
                    'maskImage' => $variant->mask_url,
                    'imageTransform' => $variant->image_transform,
                    'lumina' => $variant->lumina,
                    'description' => $variant->description,
                ];
            });

            return [
                'id' => (string)$product->id,
                'name' => $product->name,
                'price' => number_format((float)$product->sale_price, 2, '.', ''),
                'image' => $product->image_url,
                'maskImage' => $product->mask_url,
                'description' => $product->catalog_description ?? $product->description,
                'colors' => $colors,
                'types' => $types,
                'baseHue' => (float)$product->base_hue,
                'imageTransform' => $product->image_transform,
            ];
        });

        // Obtener ajustes globales
        $settings = DB::table('catalog_settings')->pluck('value', 'key');

        return response()->json([
            'products' => $formattedProducts,
            'settings' => [
                'sceneBackground' => $settings['background_scene_url'] ?? null,
                'isActive' => $settings['catalog_is_active'] ?? 'true',
            ]
        ]);
    }
}
