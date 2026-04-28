<x-filament-panels::page>
    <div 
        x-data="{
            locations: {{ json_encode($locations) }},
            map: null,
            markers: {},
            
            init() {
                this.initMap();
                // Polling cada 30 segundos para refrescar posiciones
                setInterval(() => this.refreshLocations(), 30000);
            },
            
            initMap() {
                this.map = L.map('sales-map').setView([14.6349, -90.5069], 12);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(this.map);
                
                this.renderMarkers();
            },
            
            renderMarkers() {
                this.locations.forEach(loc => {
                    const iconColor = loc.is_online ? '#4f46e5' : '#9ca3af';
                    const icon = L.divIcon({
                        className: 'custom-div-icon',
                        html: `
                            <div style='display: flex; flex-direction: column; align-items: center; justify-content: center; transform: translateY(-50%);'>
                                <div style='background-color: ${iconColor}; width: 14px; height: 14px; border: 2px solid white; border-radius: 50%; box-shadow: 0 0 10px rgba(0,0,0,0.3); z-index: 10;'></div>
                                <div style='margin-top: 4px; padding: 2px 6px; background-color: white; color: #1f2937; font-size: 11px; font-weight: bold; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); white-space: nowrap; border: 1px solid #e5e7eb;'>
                                    ${loc.name}
                                </div>
                            </div>
                        `,
                        iconSize: [100, 40],
                        iconAnchor: [50, 14]
                    });

                    const marker = L.marker([loc.lat, loc.lng], { icon: icon })
                        .addTo(this.map)
                        .bindPopup(`
                            <div class='p-2'>
                                <p class='font-bold text-gray-900'>${loc.name}</p>
                                <p class='text-xs text-gray-500'>Ultima conexión: ${loc.updated_at}</p>
                                <p class='text-xs text-gray-500'>Velocidad: ${loc.speed ? loc.speed.toFixed(1) + ' km/h' : '0 km/h'}</p>
                                <div class='mt-2'>
                                    <span class='px-2 py-0.5 rounded-full text-[10px] ${loc.is_online ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'}'>
                                        ${loc.is_online ? 'EN LINEA' : 'DESCONECTADO'}
                                    </span>
                                </div>
                            </div>
                        `);
                    
                    this.markers[loc.user_id] = marker;
                });

                if (this.locations.length > 0) {
                    const group = new L.featureGroup(Object.values(this.markers));
                    this.map.fitBounds(group.getBounds().pad(0.1));
                }
            },
            
            async refreshLocations() {
                try {
                    // Aquí podrías llamar a una pequeña API o refrescar vía Livewire
                    // Por simplicidad en este paso, recargamos los datos
                    window.location.reload();
                } catch (e) {}
            }
        }"
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden"
    >
        <div class="relative w-full" style="height: 700px;">
            <div id="sales-map" class="absolute inset-0 z-0" wire:ignore></div>
            
            <!-- Botón X para cerrar/regresar -->
            <a href="{{ \App\Filament\Resources\SaleResource::getUrl('index') }}" 
               class="absolute top-4 right-4 z-[9999] bg-white dark:bg-gray-800 p-2 rounded-full shadow-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors border border-gray-200 dark:border-gray-700 cursor-pointer"
               title="Cerrar Mapa">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    </div>

    {{-- Assets de Leaflet --}}
    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
</x-filament-panels::page>
