<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebugController extends Controller
{
    /**
     * Debug endpoint to see real errors from the SaleResource form.
     * TEMPORARY - Remove after debugging.
     */
    public function debugSaleCreate()
    {
        try {
            // 1. Test basic DB connectivity
            $dbOk = \DB::select('SELECT 1 as ok');
            
            // 2. Test if settings table works
            $settings = \App\Models\Setting::count();
            
            // 3. Test warehouses
            $warehouses = \App\Models\Warehouse::where('is_active', true)->pluck('name', 'id')->toArray();
            $factory = \App\Models\Warehouse::where('is_factory', true)->first();
            
            // 4. Test products with stock
            $factoryId = $factory?->id;
            $products = [];
            if ($factoryId) {
                $products = \App\Models\Product::where('type', 'finished_product')
                    ->where('is_active', true)
                    ->whereHas('stocks', fn($q) => $q->where('warehouse_id', $factoryId)->where('quantity', '>', 0))
                    ->pluck('name', 'id')
                    ->toArray();
            }

            // 5. Test Sale model
            $lastSale = \App\Models\Sale::latest('id')->first();
            $nextNumber = \App\Models\Sale::generateUniqueSaleNumber();

            // 6. Test SaleItem fillable
            $saleItem = new \App\Models\SaleItem();
            $saleItemFillable = $saleItem->getFillable();

            // 7. Test SalePayment fillable
            $salePayment = new \App\Models\SalePayment();
            $salePaymentFillable = $salePayment->getFillable();

            // 8. Check sale_items table structure
            $saleItemColumns = \DB::select("SHOW COLUMNS FROM sale_items");
            
            // 9. Check sale_payments table structure
            $salePaymentColumns = \DB::select("SHOW COLUMNS FROM sale_payments");

            // 10. Try to instantiate the Filament resource form
            $formTest = 'not_tested';
            try {
                $resource = \App\Filament\Resources\SaleResource::class;
                $formTest = 'resource_class_exists';
            } catch (\Throwable $e) {
                $formTest = 'ERROR: ' . $e->getMessage();
            }

            return response()->json([
                'status' => 'success',
                'db_ok' => !empty($dbOk),
                'settings_count' => $settings,
                'warehouses' => $warehouses,
                'factory' => $factory?->name ?? 'NO FACTORY FOUND',
                'factory_id' => $factoryId,
                'products_in_factory' => $products,
                'products_count' => count($products),
                'last_sale' => $lastSale?->sale_number ?? 'NO SALES',
                'next_sale_number' => $nextNumber,
                'sale_item_fillable' => $saleItemFillable,
                'sale_payment_fillable' => $salePaymentFillable,
                'sale_items_columns' => array_map(fn($c) => [
                    'Field' => $c->Field,
                    'Type' => $c->Type,
                    'Null' => $c->Null,
                    'Default' => $c->Default,
                ], $saleItemColumns),
                'sale_payments_columns' => array_map(fn($c) => [
                    'Field' => $c->Field,
                    'Type' => $c->Type,
                    'Null' => $c->Null,
                    'Default' => $c->Default,
                ], $salePaymentColumns),
                'form_test' => $formTest,
            ], 200, [], JSON_PRETTY_PRINT);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 20),
            ], 500, [], JSON_PRETTY_PRINT);
        }
    }
}
