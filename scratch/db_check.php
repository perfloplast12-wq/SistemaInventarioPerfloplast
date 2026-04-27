<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

try {
    echo "Checking database connection...\n";
    DB::connection()->getPdo();
    echo "Connection OK!\n";

    echo "Counting products...\n";
    $count = Product::query()->count();
    echo "Total Products: $count\n";

    echo "Running critical stock query...\n";
    $criticalStockCount = DB::table('products')
        ->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')
        ->where('products.is_active', true)
        ->whereNull('products.deleted_at')
        ->groupBy('products.id', 'products.units_per_presentation')
        ->havingRaw('COALESCE(SUM(stocks.quantity), 0) / COALESCE(NULLIF(products.units_per_presentation, 0), 1) <= 10')
        ->get(['products.id'])
        ->count();
    echo "Critical Stock Count: $criticalStockCount\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
