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
                    <div class='flex flex-col items-center justify-end' style='transform: translateY(-100%); margin-top: 12px;'>
                        <!-- Elegant Soft Radial Pulse -->
                        <div class='absolute w-[60px] h-[60px] bg-red-500/25 rounded-full animate-ping' style='animation-duration: 2.5s; pointer-events: none;'></div>
                        
                        <!-- Premium Teardrop SVG Pin -->
                        <div class='relative transition-transform duration-300 hover:scale-115 cursor-pointer' style='filter: drop-shadow(0px 6px 12px rgba(0,0,0,0.4));'>
                            <svg width="46" height="56" viewBox="0 0 24 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- Teardrop shape with premium gradient border and fill -->
                                <path d="M12 0C5.37258 0 0 5.37258 0 12C0 19.5 12 30 12 30C12 30 24 19.5 24 12C24 5.37258 18.6274 0 12 0Z" fill="url(#premiumPinGradient)" stroke="#ffffff" stroke-width="1.8" stroke-linejoin="round"/>
                                
                                <!-- Large Outer White Ring for High Contrast -->
                                <circle cx="12" cy="11.5" r="5.5" fill="#ffffff" />
                                
                                <!-- Sleek House SVG Icon in vibrant Coral-Red -->
                                <path d="M9.5 11.5L12 9.5L14.5 11.5V14.5H9.5V11.5Z" fill="#ff2d55" />
                                <path d="M11.2 14.5V12.5H12.8V14.5" stroke="#ffffff" stroke-width="0.8" stroke-linecap="round"/>
                                
                                <defs>
                                    <linearGradient id="premiumPinGradient" x1="12" y1="0" x2="12" y2="30" gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#FF453A"/>
                                        <stop offset="60%" stop-color="#FF2D55"/>
                                        <stop offset="100%" stop-color="#C9142B"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                    </div>
                `;

                const markerIcon = L.divIcon({
                    className: '',
                    html: iconHtml,
                    iconSize: [46, 56],
                    iconAnchor: [23, 44]
                });

                L.marker([{{ $lat }}, {{ $lng }}], { icon: markerIcon })
                    .addTo(this.map)
                    .bindPopup(`
                        <div style='padding: 10px; font-family: system-ui, sans-serif; min-width: 200px;'>
                            <h4 style='margin: 0 0 6px 0; font-weight: 800; color: #ff2d55; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;'>📍 Punto de Pre-venta</h4>
                            <p style='margin: 0; font-size: 13px; font-weight: 700; color: #111827;'>{{ $record->customer_name }}</p>
                            <p style='margin: 6px 0 0 0; font-size: 11px; color: #4b5563; line-height: 1.5; background: #f3f4f6; padding: 6px 8px; border-radius: 6px;'>{{ $record->delivery_address }}</p>
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
