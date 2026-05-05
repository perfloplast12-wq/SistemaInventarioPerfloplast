<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebugController extends Controller
{
    /**
     * Debug endpoint - tries to render the actual CreateSale Livewire component
     * to capture the exact PHP error.
     * TEMPORARY - Remove after debugging.
     */
    public function debugSaleCreate()
    {
        try {
            // Step 1: Test if we can instantiate the Livewire component
            $component = new \App\Filament\Resources\SaleResource\Pages\CreateSale();
            
            return response()->json([
                'step' => 'component_instantiated',
                'status' => 'success',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'step' => 'component_instantiation',
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e),
                'trace' => array_slice(array_map(function ($t) {
                    return ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?') . ' ' . ($t['class'] ?? '') . '::' . ($t['function'] ?? '');
                }, $e->getTrace()), 0, 25),
            ], 200, [], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Test rendering the form with all closures executed.
     */
    public function debugSaleForm()
    {
        try {
            // Test each form closure individually
            $results = [];
            
            // 1. Test warehouse query
            $results['warehouse_options'] = \App\Models\Warehouse::where('is_active', true)->pluck('name', 'id')->toArray();
            
            // 2. Test truck query
            $results['truck_options'] = \App\Models\Truck::where('is_active', true)
                ->get()
                ->mapWithKeys(fn ($truck) => [$truck->id => $truck->name . " [" . ($truck->plate ?? 'N/A') . "]"])
                ->toArray();
            
            // 3. Test product query with stock
            $warehouseId = \App\Models\Warehouse::where('is_factory', true)->first()?->id;
            if ($warehouseId) {
                $results['product_options'] = \App\Models\Product::where('type', 'finished_product')
                    ->where('is_active', true)
                    ->whereHas('stocks', fn($q) => $q->where('warehouse_id', $warehouseId)->where('quantity', '>', 0))
                    ->pluck('name', 'id')
                    ->toArray();
            }
            
            // 4. Test color query for first product
            $firstProductId = array_key_first($results['product_options'] ?? []);
            if ($firstProductId) {
                $product = \App\Models\Product::with('color')->find($firstProductId);
                $results['product_color'] = $product?->color?->display_name ?? 'No color relation';
                $results['product_has_color_relation'] = method_exists($product, 'color');
                
                $stocks = \App\Models\Stock::with('color')
                    ->where('product_id', $firstProductId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('quantity', '>', 0)
                    ->get();
                    
                $results['stocks_for_product'] = $stocks->map(fn($s) => [
                    'id' => $s->id,
                    'color_id' => $s->color_id,
                    'color_name' => $s->color?->display_name ?? $s->color?->name ?? 'null',
                    'quantity' => $s->quantity,
                ])->toArray();
            }
            
            // 5. Test Filament form rendering
            try {
                $form = \App\Filament\Resources\SaleResource::form(
                    \Filament\Forms\Form::make(
                        app(\Filament\Forms\Contracts\HasForms::class)
                    )
                );
                $results['form_render'] = 'success';
            } catch (\Throwable $e) {
                $results['form_render'] = 'ERROR: ' . $e->getMessage();
                $results['form_render_file'] = $e->getFile() . ':' . $e->getLine();
                $results['form_render_trace'] = array_slice(array_map(function ($t) {
                    return ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?') . ' ' . ($t['class'] ?? '') . '::' . ($t['function'] ?? '');
                }, $e->getTrace()), 0, 15);
            }
            
            // 6. Check InvoiceService class exists
            $results['invoice_service_exists'] = class_exists(\App\Services\InvoiceService::class);
            
            // 7. Check all required classes
            $classes = [
                'Sale' => \App\Models\Sale::class,
                'SaleItem' => \App\Models\SaleItem::class,
                'SalePayment' => \App\Models\SalePayment::class,
                'Product' => \App\Models\Product::class,
                'Warehouse' => \App\Models\Warehouse::class,
                'Truck' => \App\Models\Truck::class,
                'Stock' => \App\Models\Stock::class,
                'Color' => \App\Models\Color::class,
                'SaleResource' => \App\Filament\Resources\SaleResource::class,
                'CreateSale' => \App\Filament\Resources\SaleResource\Pages\CreateSale::class,
                'SaleService' => \App\Services\SaleService::class,
                'InvoiceService' => \App\Services\InvoiceService::class,
                'SalesExport' => \App\Exports\SalesExport::class,
            ];
            
            foreach ($classes as $name => $fqcn) {
                $results['class_exists'][$name] = class_exists($fqcn);
            }
            
            // 8. Check APP_DEBUG and APP_ENV
            $results['app_debug'] = config('app.debug');
            $results['app_env'] = config('app.env');
            $results['app_url'] = config('app.url');

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200, [], JSON_PRETTY_PRINT);
            
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e),
                'trace' => array_slice(array_map(function ($t) {
                    return ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?') . ' ' . ($t['class'] ?? '') . '::' . ($t['function'] ?? '');
                }, $e->getTrace()), 0, 25),
            ], 200, [], JSON_PRETTY_PRINT);
        }
    }

    public function viewLog()
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath) || !is_readable($logPath)) {
            return response('No log file found or not readable at ' . $logPath, 200);
        }
        
        try {
            $file = fopen($logPath, 'r');
            if (!$file) {
                return response('Could not open log file.', 200);
            }
            
            $filesize = filesize($logPath);
            $bytesToRead = min(20000, $filesize);
            
            if ($bytesToRead > 0) {
                fseek($file, -$bytesToRead, SEEK_END);
                $content = fread($file, $bytesToRead);
            } else {
                $content = "File is empty.";
            }
            fclose($file);
            
            return response('<pre style="background:#1e1e1e;color:#d4d4d4;padding:20px;white-space:pre-wrap;font-family:monospace;font-size:13px;">' . htmlspecialchars($content) . '</pre>', 200);
        } catch (\Throwable $e) {
            return response('Error reading log: ' . $e->getMessage(), 200);
        }
    }

    public function runMigrations()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            return response()->json([
                'status' => 'success',
                'message' => 'Migraciones ejecutadas correctamente.',
                'output' => \Illuminate\Support\Facades\Artisan::output()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 200); // 200 so we can read it
        }
    }
}
