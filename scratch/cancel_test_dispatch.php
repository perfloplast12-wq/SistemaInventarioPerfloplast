<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Dispatch;
use App\Services\DispatchService;

$service = app(DispatchService::class);
$dispatches = Dispatch::where('status', 'in_progress')->get();

if ($dispatches->isEmpty()) {
    echo "No se encontraron despachos en progreso.\n";
} else {
    foreach ($dispatches as $dispatch) {
        echo "Cancelando despacho #{$dispatch->dispatch_number} (ID: {$dispatch->id})...\n";
        try {
            $service->cancel($dispatch);
            echo "Despacho #{$dispatch->dispatch_number} cancelado y stock revertido con éxito.\n";
        } catch (\Exception $e) {
            echo "Error al cancelar despacho #{$dispatch->dispatch_number}: " . $e->getMessage() . "\n";
        }
    }
}
