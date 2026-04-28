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

            <!-- Botón "Ver todos" -->
            <button id="btn-ver-todos"
                    onclick="window._salesMapZoomToAll()"
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
        
        <style>
            /* Estilos para los marcadores personalizados */
            .vendor-marker {
                display: flex;
                flex-direction: column;
                align-items: center;
                transform: translateY(-50%);
            }
            .vendor-marker-pin {
                width: 40px;
                height: 40px;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.4);
                border: 3px solid white;
            }
            .vendor-marker-pin.online {
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
            }
            .vendor-marker-pin.offline {
                background: linear-gradient(135deg, #ef4444, #b91c1c);
            }
            .vendor-marker-icon {
                transform: rotate(45deg);
                font-size: 18px;
                line-height: 1;
            }
            .vendor-marker-pulse {
                position: absolute;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                animation: pulse-ring 2s ease-out infinite;
            }
            .vendor-marker-pulse.online {
                background: rgba(79, 70, 229, 0.3);
            }
            @keyframes pulse-ring {
                0% { transform: scale(0.8); opacity: 1; }
                100% { transform: scale(2.2); opacity: 0; }
            }
            .vendor-marker-label {
                margin-top: 6px;
                padding: 3px 8px;
                background: rgba(0,0,0,0.75);
                color: white;
                font-size: 11px;
                font-weight: 700;
                border-radius: 10px;
                white-space: nowrap;
                letter-spacing: 0.3px;
                box-shadow: 0 1px 4px rgba(0,0,0,0.3);
            }
            .vendor-marker-status {
                position: absolute;
                top: -2px;
                right: -2px;
                width: 12px;
                height: 12px;
                border-radius: 50%;
                border: 2px solid white;
                z-index: 10;
            }
            .vendor-marker-status.online {
                background: #22c55e;
            }
            .vendor-marker-status.offline {
                background: #ef4444;
            }
        </style>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('salesMapComponent', (initialLocations) => ({
                    locations: initialLocations,
                    map: null,
                    markers: {},
                    defaultBounds: null,
                    isZoomedIn: false,
                    refreshTimer: null,
                    
                    init() {
                        this.initMap();
                        // Actualización silenciosa cada 15 segundos (sin recargar la página)
                        this.refreshTimer = setInterval(() => this.silentRefresh(), 15000);
                        window._salesMapZoomToAll = () => this.zoomToAll();
                    },
                    
                    initMap() {
                        this.map = L.map('sales-map').setView([14.6349, -90.5069], 12);
                        
                        // Capa de Google Maps (muestra negocios, calles detalladas, etc.)
                        L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                            attribution: '&copy; Google Maps',
                            maxZoom: 20
                        }).addTo(this.map);
                        
                        this.renderMarkers();
                    },
                    
                    createMarkerIcon(loc) {
                        const status = loc.is_online ? 'online' : 'offline';
                        return L.divIcon({
                            className: 'custom-div-icon',
                            html: `
                                <div class="vendor-marker">
                                    <div style="position: relative;">
                                        ${loc.is_online ? '<div class="vendor-marker-pulse online"></div>' : ''}
                                        <div class="vendor-marker-pin ${status}">
                                            <span class="vendor-marker-icon">👤</span>
                                        </div>
                                        <div class="vendor-marker-status ${status}"></div>
                                    </div>
                                    <div class="vendor-marker-label">${loc.name}</div>
                                </div>
                            `,
                            iconSize: [50, 65],
                            iconAnchor: [25, 50],
                            popupAnchor: [0, -50]
                        });
                    },

                    createPopupContent(loc) {
                        const statusDot = loc.is_online ? '#22c55e' : '#ef4444';
                        const timeLabel = loc.is_online 
                            ? `<strong>Conectado ahora</strong>` 
                            : `<strong>Última conexión:</strong> ${loc.updated_at}`;
                        const timeColor = loc.is_online ? '#15803d' : '#ef4444';
                        return `
                            <div style='min-width: 240px; padding: 14px; font-family: -apple-system, sans-serif;'>
                                <div style='display: flex; align-items: center; gap: 10px; margin-bottom: 12px;'>
                                    <div style='width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, ${loc.is_online ? "#4f46e5, #7c3aed" : "#ef4444, #b91c1c"}); display: flex; align-items: center; justify-content: center;'>
                                        <span style='font-size: 18px;'>👤</span>
                                    </div>
                                    <div>
                                        <p style='font-weight: 700; font-size: 15px; color: #111827; margin: 0;'>${loc.name}</p>
                                        <span style='display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; ${loc.is_online ? "background: #dcfce7; color: #15803d;" : "background: #fef2f2; color: #dc2626;"}'>
                                            <span style='width: 6px; height: 6px; border-radius: 50%; background: ${statusDot};'></span>
                                            ${loc.is_online ? 'EN LÍNEA' : 'FUERA DE LÍNEA'}
                                        </span>
                                    </div>
                                </div>
                                <div style='border-top: 1px solid #e5e7eb; padding-top: 10px; display: grid; gap: 6px;'>
                                    <div style='display: flex; align-items: center; gap: 6px; font-size: 12px; color: ${timeColor};'>
                                        <span style='font-size: 14px;'>${loc.is_online ? '🟢' : '🔴'}</span>
                                        <span>${timeLabel}</span>
                                    </div>
                                    ${!loc.is_online ? `
                                    <div style='display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280;'>
                                        <span style='font-size: 14px;'>🕐</span>
                                        <span><strong>Última posición registrada:</strong> ${loc.last_seen_exact}</span>
                                    </div>` : ''}
                                    <div style='display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280;'>
                                        <span style='font-size: 14px;'>📍</span>
                                        <span><strong>Coordenadas:</strong> ${loc.lat.toFixed(5)}, ${loc.lng.toFixed(5)}</span>
                                    </div>
                                    <div style='display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280;'>
                                        <span style='font-size: 14px;'>🚗</span>
                                        <span><strong>Velocidad:</strong> ${loc.speed ? loc.speed.toFixed(1) + ' km/h' : '0 km/h'}</span>
                                    </div>
                                    <div style='display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280;'>
                                        <span style='font-size: 14px;'>📡</span>
                                        <span><strong>Precisión GPS:</strong> ${loc.accuracy ? loc.accuracy + ' m' : 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    },
                    
                    renderMarkers() {
                        const self = this;
                        this.locations.forEach(loc => {
                            const icon = this.createMarkerIcon(loc);

                            const marker = L.marker([loc.lat, loc.lng], { icon: icon })
                                .addTo(this.map)
                                .bindPopup(this.createPopupContent(loc), { 
                                    autoClose: false, 
                                    closeOnClick: false,
                                    maxWidth: 300
                                });

                            // Al hacer clic: solo zoom la primera vez
                            marker.on('click', function(e) {
                                if (!self.isZoomedIn) {
                                    self.map.flyTo([loc.lat, loc.lng], 16, { duration: 1 });
                                    self.isZoomedIn = true;
                                    document.getElementById('btn-ver-todos').style.display = 'flex';
                                    setTimeout(() => marker.closePopup(), 10);
                                }
                            });
                            
                            this.markers[loc.user_id] = { marker, loc };
                        });

                        if (this.locations.length > 0) {
                            const group = new L.featureGroup(Object.values(this.markers).map(m => m.marker));
                            this.defaultBounds = group.getBounds().pad(0.1);
                            this.map.fitBounds(this.defaultBounds);
                        }
                    },

                    // Actualización silenciosa: obtiene nuevas coordenadas y mueve los marcadores
                    async silentRefresh() {
                        try {
                            const response = await fetch('/api/sales-locations', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });
                            if (!response.ok) return;
                            
                            const newLocations = await response.json();
                            this.locations = newLocations;

                            newLocations.forEach(loc => {
                                if (this.markers[loc.user_id]) {
                                    // Vendedor existente: actualizar posición suavemente
                                    const existing = this.markers[loc.user_id];
                                    const newLatLng = L.latLng(loc.lat, loc.lng);
                                    existing.marker.setLatLng(newLatLng);
                                    existing.marker.setIcon(this.createMarkerIcon(loc));
                                    existing.marker.setPopupContent(this.createPopupContent(loc));
                                    existing.loc = loc;
                                } else {
                                    // Nuevo vendedor: agregar marcador
                                    const icon = this.createMarkerIcon(loc);
                                    const marker = L.marker([loc.lat, loc.lng], { icon: icon })
                                        .addTo(this.map)
                                        .bindPopup(this.createPopupContent(loc), {
                                            autoClose: false,
                                            closeOnClick: false,
                                            maxWidth: 300
                                        });
                                    
                                    const self = this;
                                    marker.on('click', function(e) {
                                        if (!self.isZoomedIn) {
                                            self.map.flyTo([loc.lat, loc.lng], 16, { duration: 1 });
                                            self.isZoomedIn = true;
                                            document.getElementById('btn-ver-todos').style.display = 'flex';
                                            setTimeout(() => marker.closePopup(), 10);
                                        }
                                    });

                                    this.markers[loc.user_id] = { marker, loc };
                                }
                            });

                            // Actualizar bounds por si hay nuevos vendedores
                            if (Object.keys(this.markers).length > 0) {
                                const group = new L.featureGroup(Object.values(this.markers).map(m => m.marker));
                                this.defaultBounds = group.getBounds().pad(0.1);
                            }
                        } catch (e) {
                            console.log('Error actualizando ubicaciones:', e);
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
                }));
            });
        </script>
    @endpush
</x-filament-panels::page>
