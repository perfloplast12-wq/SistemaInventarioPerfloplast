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
                        html: `<div style='background-color: ${iconColor}; width: 12px; height: 12px; border: 2px solid white; border-radius: 50%; box-shadow: 0 0 10px rgba(0,0,0,0.3);'></div>`,
                        iconSize: [12, 12],
                        iconAnchor: [6, 6]
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
        <div id="sales-map" style="height: 700px; width: 100%;" wire:ignore></div>
    </div>

    {{-- Assets de Leaflet --}}
    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
</x-filament-panels::page>
