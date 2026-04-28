<x-filament-panels::page>
    <div 
        x-data="salesMapComponent({{ \Illuminate\Support\Js::from($locations) }})"
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden"
    >
        <div class="relative w-full" style="height: 700px;">
            <div id="sales-map" class="absolute inset-0 z-0" wire:ignore></div>
            
            <!-- Botón X para cerrar/regresar -->
            <a href="{{ \App\Filament\Resources\SaleResource::getUrl('index') }}" 
               class="absolute bg-white dark:bg-gray-800 p-2 rounded-full shadow-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors border border-gray-200 dark:border-gray-700 cursor-pointer"
               style="z-index: 9999; top: 1rem; right: 1rem;"
               title="Cerrar Mapa">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>

            <!-- Botón "Ver todos" (aparece al hacer zoom en un vendedor) -->
            <button id="btn-ver-todos"
                    onclick="document.querySelector('[x-data]').__x.$data.zoomToAll()"
                    class="absolute bg-white dark:bg-gray-800 px-4 py-2 rounded-full shadow-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors border border-gray-200 dark:border-gray-700 cursor-pointer items-center gap-2 text-sm font-semibold"
                    style="z-index: 9999; top: 1rem; right: 4rem; display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                </svg>
                Ver todos
            </button>
        </div>
    </div>

    {{-- Assets de Leaflet --}}
    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('salesMapComponent', (initialLocations) => ({
                    locations: initialLocations,
                    map: null,
                    markers: {},
                    defaultBounds: null,
                    isZoomedIn: false,
                    
                    init() {
                        this.initMap();
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
                        const self = this;
                        this.locations.forEach(loc => {
                            const iconColor = loc.is_online ? '#4f46e5' : '#9ca3af';
                            const statusDot = loc.is_online ? '#22c55e' : '#9ca3af';
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
                                    <div style='min-width: 220px; padding: 12px; font-family: sans-serif;'>
                                        <div style='display: flex; align-items: center; gap: 8px; margin-bottom: 10px;'>
                                            <div style='width: 10px; height: 10px; border-radius: 50%; background: ${statusDot};'></div>
                                            <p style='font-weight: bold; font-size: 15px; color: #111827; margin: 0;'>${loc.name}</p>
                                        </div>
                                        <div style='border-top: 1px solid #e5e7eb; padding-top: 8px;'>
                                            <p style='font-size: 12px; color: #6b7280; margin: 4px 0;'>
                                                <strong>Última posición:</strong> ${loc.last_seen_exact || loc.updated_at}
                                            </p>
                                            <p style='font-size: 12px; color: #6b7280; margin: 4px 0;'>
                                                <strong>Coordenadas:</strong> ${loc.lat.toFixed(5)}, ${loc.lng.toFixed(5)}
                                            </p>
                                            <p style='font-size: 12px; color: #6b7280; margin: 4px 0;'>
                                                <strong>Velocidad:</strong> ${loc.speed ? loc.speed.toFixed(1) + ' km/h' : '0 km/h'}
                                            </p>
                                            <p style='font-size: 12px; color: #6b7280; margin: 4px 0;'>
                                                <strong>Precisión GPS:</strong> ${loc.accuracy ? loc.accuracy + ' m' : 'N/A'}
                                            </p>
                                        </div>
                                        <div style='margin-top: 8px;'>
                                            <span style='display: inline-block; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; ${loc.is_online ? "background: #dcfce7; color: #15803d;" : "background: #f3f4f6; color: #6b7280;"}'>
                                                ${loc.is_online ? '● EN LÍNEA' : '○ DESCONECTADO'}
                                            </span>
                                        </div>
                                    </div>
                                `, { autoClose: false, closeOnClick: false });

                            // Al hacer clic: solo zoom, NO abrir popup automáticamente
                            marker.on('click', function(e) {
                                if (!self.isZoomedIn) {
                                    // Primer clic: solo zoom
                                    self.map.flyTo([loc.lat, loc.lng], 16, { duration: 1 });
                                    self.isZoomedIn = true;
                                    document.getElementById('btn-ver-todos').style.display = 'flex';
                                    // Cerrar popup si se abrió automáticamente
                                    setTimeout(() => marker.closePopup(), 10);
                                }
                                // Si ya está en zoom, el clic abre el popup normalmente
                            });
                            
                            this.markers[loc.user_id] = marker;
                        });

                        if (this.locations.length > 0) {
                            const group = new L.featureGroup(Object.values(this.markers));
                            this.defaultBounds = group.getBounds().pad(0.1);
                            this.map.fitBounds(this.defaultBounds);
                        }
                    },

                    zoomToAll() {
                        if (this.defaultBounds) {
                            this.map.flyToBounds(this.defaultBounds, { duration: 1 });
                        }
                        this.isZoomedIn = false;
                        this.map.closePopup();
                        document.getElementById('btn-ver-todos').style.display = 'none';
                    },
                    
                    async refreshLocations() {
                        window.location.reload();
                    }
                }));
            });
        </script>
    @endpush
</x-filament-panels::page>
