<?php
/**
 * Production Database Diagnostics Script.
 * Verifies if columns 'lat' and 'lng' exist on 'sales' and 'orders' tables on production.
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $db = \Illuminate\Support\Facades\DB::connection();
    echo "=== DB CONNECTION SUCCESSFUL ===\n\n";

    // Check sales table
    echo "--- SALES TABLE COLUMNS ---\n";
    $salesCols = $db->select("SHOW COLUMNS FROM sales");
    $salesHasLat = false;
    $salesHasLng = false;
    foreach ($salesCols as $col) {
        echo "Field: {$col->Field} | Type: {$col->Type} | Null: {$col->Null}\n";
        if ($col->Field === 'lat') $salesHasLat = true;
        if ($col->Field === 'lng') $salesHasLng = true;
    }
    echo "sales has 'lat'?: " . ($salesHasLat ? "YES" : "NO") . "\n";
    echo "sales has 'lng'?: " . ($salesHasLng ? "YES" : "NO") . "\n\n";

    // Check orders table
    echo "--- ORDERS TABLE COLUMNS ---\n";
    $ordersCols = $db->select("SHOW COLUMNS FROM orders");
    $ordersHasLat = false;
    $ordersHasLng = false;
    foreach ($ordersCols as $col) {
        echo "Field: {$col->Field} | Type: {$col->Type} | Null: {$col->Null}\n";
        if ($col->Field === 'lat') $ordersHasLat = true;
        if ($col->Field === 'lng') $ordersHasLng = true;
    }
    echo "orders has 'lat'?: " . ($ordersHasLat ? "YES" : "NO") . "\n";
    echo "orders has 'lng'?: " . ($ordersHasLng ? "YES" : "NO") . "\n\n";

    // Check pending migrations
    echo "--- MIGRATIONS STATUS ---\n";
    $migrations = $db->select("SELECT migration, batch FROM migrations ORDER BY id DESC LIMIT 10");
    foreach ($migrations as $m) {
        echo "Migration: {$m->migration} | Batch: {$m->batch}\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
