@php
    $record = $getRecord();
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

    // 2. Query each active dispatch ID individually (perfectly indexed, ultra-fast)
    $latest = null;
    foreach ($activeDispatchIds as $aid) {
        $loc = \App\Models\DispatchLocation::where('dispatch_id', $aid)
            ->orderByDesc('created_at')
            ->first();
        if ($loc) {
            if (!$latest || $loc->created_at->gt($latest->created_at)) {
                $latest = $loc;
            }
        }
    }

    // 3. Fallback: check other recent dispatches for this truck
    if (!$latest && $record->truck_id) {
        $otherIds = \App\Models\Dispatch::where('truck_id', $record->truck_id)
            ->whereNotIn('id', $activeDispatchIds)
            ->orderByDesc('id')
            ->limit(5)
            ->pluck('id');
        foreach ($otherIds as $oid) {
            $loc = \App\Models\DispatchLocation::where('dispatch_id', $oid)
                ->orderByDesc('created_at')
                ->first();
            if ($loc) {
                if (!$latest || $loc->created_at->gt($latest->created_at)) {
                    $latest = $loc;
                }
            }
        }
    }

    $locations = $latest ? collect([$latest]) : collect();
@endphp

<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 550px;" 
     x-data="leafletRouteMap({
        dispatchId: {{ $dispatchId }},
        dispatchNumber: '{{ $dispatchNumber ?? '' }}',
        driverName: '{{ $driverName ?? 'Sin asignar' }}',
        truckName: '{{ $truckName ?? 'Sin asignar' }}',
        routeName: '{{ $routeName ?? 'Sin ruta' }}',
        dispatchStatus: '{{ $dispatchStatus ?? 'pending' }}',
        locations: '{{ base64_encode(json_encode($locations)) }}'
     })"
     x-init="$nextTick(() => { init(); })"
>
    <style>
        @keyframes truck-pulse {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.2); opacity: 0; }
        }
    </style>

    <div x-ref="mapContainer" class="w-full h-[550px]" style="height: 550px;" wire:ignore></div>
</div>
