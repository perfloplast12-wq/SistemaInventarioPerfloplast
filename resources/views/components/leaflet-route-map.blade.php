<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900" style="min-height: 500px;">
    @php
        $mapId = 'map_' . md5(uniqid());
        $encodedData = base64_encode(json_encode($locations));
    @endphp

    <div 
        x-data="{
            map: null,
            isLoaded: false,
            init() {
                this.loadAssets().then(() => this.render());
            },
            async loadAssets() {
                if (window.L && window.L.Control.Geocoder) return;
                
                return new Promise((resolve) => {
                    // CSS Leaflet
                    if (!document.getElementById('leaflet-css')) {
                        const link = document.createElement('link');
                        link.id = 'leaflet-css';
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        document.head.appendChild(link);
                    }

                    // CSS Geocoder
                    if (!document.getElementById('geocoder-css')) {
                        const gCss = document.createElement('link');
                        gCss.id = 'geocoder-css';
                        gCss.rel = 'stylesheet';
                        gCss.href = 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css';
                        document.head.appendChild(gCss);
                    }

                    // JS Leaflet
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = () => {
                        // JS Geocoder
                        const gScript = document.createElement('script');
                        gScript.src = 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js';
                        gScript.onload = () => resolve();
                        document.head.appendChild(gScript);
                    };
                    document.head.appendChild(script);
                });
            },
            render() {
                const el = this.$refs.mapContainer;
                if (!el || typeof L === 'undefined') return;

                if (el._leaflet_id) {
                    el._leaflet_id = null;
                    el.innerHTML = '';
                }

                this.map = L.map(el).setView([15.47, -90.37], 7);

                // Capas Base
                const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; CartoDB',
                    maxZoom: 20
                });

                const osmStreet = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19
                });

                const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: '&copy; Esri',
                    maxZoom: 18
                });

                cartoLight.addTo(this.map);

                const baseMaps = {
                    'Mapa Claro': cartoLight,
                    'Calles (OSM)': osmStreet,
                    'Satelite': esriSatellite
                };

                L.control.layers(baseMaps, null, { position: 'topright' }).addTo(this.map);

                // Buscador
                if (L.Control.Geocoder) {
                    L.Control.geocoder({
                        defaultMarkGeocode: true,
                        placeholder: 'Buscar direccion...',
                        errorMessage: 'No se encontro.'
                    }).addTo(this.map);
                }

                let raw = [];
                try {
                    raw = JSON.parse(atob('{{ $encodedData }}'));
                } catch(e) { console.error('Error b64'); }

                const filtered = raw.filter(loc => {
                    const lat = parseFloat(loc.lat);
                    const lng = parseFloat(loc.lng);
                    return lat > 10 && lat < 22 && lng > -100 && lng < -80;
                });

                const locations = filtered.length > 0 ? filtered : raw;

                if (locations.length > 0) {
                    const pts = locations.map(l => [parseFloat(l.lat), parseFloat(l.lng)]);
                    
                    // Ruta
                    L.polyline(pts, {
                        color: '#10b981',
                        weight: 6,
                        opacity: 0.8
                    }).addTo(this.map);

                    // Marcador Inicio (Naranja/Gris para diferenciar)
                    L.circleMarker(pts[0], {
                        radius: 6,
                        color: '#fff',
                        fillColor: '#64748b',
                        fillOpacity: 1,
                        weight: 2
                    }).addTo(this.map).bindPopup('Inicio de Ruta');

                    // Marcador Actual (VERDE como pidió el usuario)
                    const last = pts[pts.length - 1];
                    L.circleMarker(last, {
                        radius: 12,
                        color: '#fff',
                        fillColor: '#22c55e',
                        fillOpacity: 1,
                        weight: 4,
                        className: 'animate-pulse-marker'
                    }).addTo(this.map).bindPopup('Ubicación Actual (En vivo)');

                    this.map.fitBounds(L.polyline(pts).getBounds(), { padding: [50, 50] });
                }

                this.isLoaded = true;
                setTimeout(() => this.map.invalidateSize(), 500);
            }
        }"
        class="w-full h-full"
    >
        <style>
            .animate-pulse-marker {
                animation: marker-pulse 2s infinite;
            }
            @keyframes marker-pulse {
                0% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.7; transform: scale(1.1); }
                100% { opacity: 1; transform: scale(1); }
            }
        </style>
        <div 
            x-ref="mapContainer"
            class="w-full h-full"
            style="height: 500px; z-index: 1;"
            wire:ignore
        >
            <div x-show="!isLoaded" class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-900 z-50">
                <div class="flex flex-col items-center">
                    <svg class="animate-spin h-8 w-8 text-primary-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm text-gray-500 font-medium">Actualizando mapa...</span>
                </div>
            </div>
        </div>
    </div>
</div>
