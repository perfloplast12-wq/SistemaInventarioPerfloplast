@php
    $record = (isset($getRecord) && is_callable($getRecord)) ? $getRecord() : ($record ?? \App\Models\Dispatch::find($dispatchId ?? null));
    $dispatchId = $record->id;
    $dispatchNumber = $record->dispatch_number;
    $driverName = $record->driver?->name ?? $record->driver_name ?? 'Sin asignar';
    $truckName = $record->truck?->name ?? 'Sin asignar';
    $routeName = $record->route ?? 'Sin ruta';
    $dispatchStatus = $record->status;

    // 1. Get all active dispatches for the same truck (in progress)
    $activeDispatchIds = [];
    if ($record->truck_id) {
        $activeDispatchIds = \App\Models\Dispatch::where('truck_id', $record->truck_id)
            ->where('status', 'in_progress')
            ->pluck('id')
            ->toArray();
    }

    // Always include the current dispatch ID
    if (!in_array($record->id, $activeDispatchIds)) {
        $activeDispatchIds[] = $record->id;
    }

    // 2. Query active dispatch locations (up to 100 recent coordinates per active dispatch)
    $locations = collect();
    foreach ($activeDispatchIds as $aid) {
        $locs = \App\Models\DispatchLocation::where('dispatch_id', $aid)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
        $locations = $locations->merge($locs);
    }

    // 3. Fallback: check other recent dispatches for this truck if no locations found
    if ($locations->isEmpty() && $record->truck_id) {
        $otherIds = \App\Models\Dispatch::where('truck_id', $record->truck_id)
            ->whereNotIn('id', $activeDispatchIds)
            ->orderByDesc('id')
            ->limit(5)
            ->pluck('id');
        foreach ($otherIds as $oid) {
            $locs = \App\Models\DispatchLocation::where('dispatch_id', $oid)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
            $locations = $locations->merge($locs);
            if ($locations->isNotEmpty()) {
                break;
            }
        }
    }

    // Sort chronologically ascending to draw the route trail polyline correctly
    $locations = $locations->sortBy('created_at')->values();

    $hideOrders = $hideOrders ?? false;
    $ordersData = [];

    if (!$hideOrders) {
        $ordersData = $record->orders()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get(['id', 'order_number', 'customer_name', 'delivery_address', 'lat', 'lng', 'status'])
            ->map(function ($o) {
                return [
                    'number' => $o->order_number,
                    'customer' => $o->customer_name,
                    'address' => $o->delivery_address,
                    'lat' => (float)$o->lat,
                    'lng' => (float)$o->lng,
                    'status' => $o->status,
                ];
            })
            ->toArray();
    }
@endphp

<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 550px;" 
     x-data="leafletRouteMap({{ json_encode([
        'dispatchId' => $dispatchId,
        'dispatchNumber' => $dispatchNumber ?? '',
        'driverName' => $driverName ?? 'Sin asignar',
        'truckName' => $truckName ?? 'Sin asignar',
        'routeName' => $routeName ?? 'Sin ruta',
        'dispatchStatus' => $dispatchStatus ?? 'pending',
        'locations' => base64_encode(json_encode($locations)),
        'orders' => base64_encode(json_encode($ordersData))
     ]) }})"
     x-init="$nextTick(() => { init(); })"
>
    <style>
        @keyframes truck-pulse {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.2); opacity: 0; }
        }
    </style>

    @if($hideOrders && $locations->isEmpty())
        <div class="absolute top-4 left-4 z-[1000] bg-white/95 dark:bg-gray-900/95 backdrop-blur-md px-4 py-3 rounded-xl shadow-lg border border-amber-500/30 max-w-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-500/10 flex items-center justify-center text-xl animate-pulse">
                📡
            </div>
            <div>
                <h5 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Esperando GPS</h5>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">El piloto aún no ha iniciado el viaje o se encuentra en una zona sin señal celular.</p>
            </div>
        </div>
    @endif

    <div x-ref="mapContainer" class="w-full h-[550px]" style="height: 550px;" wire:ignore></div>
</div>
