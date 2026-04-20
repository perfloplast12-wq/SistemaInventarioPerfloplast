<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dispatches = App\Models\Dispatch::latest('id')->take(5)->get();
echo "Latest dispatch dates:\n";
foreach($dispatches as $d) {
    echo $d->dispatch_date . "\n";
}
echo "Total dispatches: " . App\Models\Dispatch::count() . "\n";
