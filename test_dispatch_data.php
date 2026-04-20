<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test the query from LogisticsRankingWidget
$start = \Carbon\Carbon::now()->startOfMonth()->startOfDay();
$end = \Carbon\Carbon::now()->endOfMonth()->endOfDay();

$data = \App\Models\Dispatch::query()
    ->whereBetween('dispatch_date', [$start, $end])
    ->select('driver_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_dispatches'))
    ->groupBy('driver_id')
    ->orderByDesc('total_dispatches')
    ->with('driver')
    ->get();

echo "Query returned " . $data->count() . " rows.\n";

$all = \App\Models\Dispatch::count();
echo "Total dispatches in DB: $all \n";

if ($all > 0) {
    $first = \App\Models\Dispatch::first();
    echo "First dispatch driver_id: " . $first->driver_id . "\n";
    echo "First dispatch dispatch_date: " . $first->dispatch_date . "\n";
}
