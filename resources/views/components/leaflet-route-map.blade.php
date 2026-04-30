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
