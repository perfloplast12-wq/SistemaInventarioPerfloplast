@php
    $record = $getRecord();
    $lat = $record->lat;
    $lng = $record->lng;
@endphp

@if($lat && $lng)
<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 400px;" 
     x-data="{
        map: null,
        init() {
            setTimeout(() => {
                if (this.map) return;
                this.map = L.map(this.$refs.mapContainer, { zoomControl: false }).setView([{{ $lat }}, {{ $lng }}], 16);
                L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                    attribution: 'Google Maps',
                    maxZoom: 20
                }).addTo(this.map);
                L.control.zoom({ position: 'bottomright' }).addTo(this.map);

                const iconHtml = `
                    <div class='flex flex-col items-center' style='transform: translateY(-50%);'>
                        <div class='relative flex items-center justify-center'>
                            <div class='absolute w-[40px] h-[40px] bg-emerald-500/30 rounded-full animate-ping' style='animation-duration: 3s;'></div>
                            <div class='relative w-[36px] h-[36px] rounded-full shadow-lg flex items-center justify-center z-10 bg-emerald-600 border-2 border-white text-white font-bold' style='background: #10b981; border-color: #ffffff;'>
                                📍
                            </div>
                        </div>
                    </div>
                `;

                const markerIcon = L.divIcon({
                    className: '',
                    html: iconHtml,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });

                L.marker([{{ $lat }}, {{ $lng }}], { icon: markerIcon })
                    .addTo(this.map)
                    .bindPopup(`
                        <div style='padding: 8px; font-family: sans-serif; min-width: 180px;'>
                            <h4 style='margin: 0 0 4px 0; font-weight: bold; color: #10b981; font-size: 13px;'>Punto de Entrega</h4>
                            <p style='margin: 0; font-size: 12px; font-weight: 600; color: #1f2937;'>{{ $record->customer_name }}</p>
                            <p style='margin: 4px 0 0 0; font-size: 11px; color: #6b7280; line-height: 1.4;'>{{ $record->delivery_address }}</p>
                        </div>
                    `)
                    .openPopup();

                // Recalculate size to avoid layout bugs
                this.map.invalidateSize();
            }, 300);
        }
     }"
     x-init="init()"
>
    <div x-ref="mapContainer" class="w-full h-[400px]" style="height: 400px;" wire:ignore></div>
</div>
@else
<div class="flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-800/40 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl">
    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-2xl mb-3 shadow-inner">
        📍
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium text-center">Este pedido no tiene coordenadas GPS de pre-venta registradas.</p>
    <p class="text-xs text-gray-400 dark:text-gray-500 text-center mt-1">La ubicación geográfica se captura automáticamente cuando los vendedores ingresan una preventa en el campo.</p>
</div>
@endif
