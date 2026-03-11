<div class='relative w-full relative-map-container'>
    <style>
        .marker-start {
            background-color: #22c55e;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 4px rgba(0,0,0,0.5);
        }
        .marker-current {
            background-color: #ef4444;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 6px rgba(0,0,0,0.6);
        }
    </style>
    
    <div 
        x-data="{
            map: null,
            locations: [],
            mapId: 'route-map-' + Math.random().toString(36).substr(2, 9),
            isLoaded: false,
            
            initData(encodedData) {
                try {
                    const jsonStr = atob(encodedData);
                    const raw = JSON.parse(jsonStr);
                    // Filtro de coordenadas para Centroamérica (10 a 22 Lat, -100 a -80 Lng)
                    // Usamos Math.max/min para evitar símbolos > y < que rompen el HTML
                    this.locations = raw.filter(loc => {
                        const latOk = Math.min(22, Math.max(10, loc.lat)) === loc.lat;
                        const lngOk = Math.min(-80, Math.max(-100, loc.lng)) === loc.lng;
                        return latOk && lngOk;
                    });
                    if (!this.locations.length) this.locations = raw;
                } catch (e) {
                    console.error('Error decodificando datos del mapa:', e);
                    this.locations = [];
                }
                setTimeout(() => this.loadAssetsAndRender(), 500);
            },

            loadAssetsAndRender() {
                if (!document.getElementById('leaflet-css')) {
                    const link = document.createElement('link');
                    link.id = 'leaflet-css';
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);
                    
                    const geocoderLink = document.createElement('link');
                    geocoderLink.id = 'leaflet-geocoder-css';
                    geocoderLink.rel = 'stylesheet';
                    geocoderLink.href = 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css';
                    document.head.appendChild(geocoderLink);
                }

                if (!document.getElementById('leaflet-js')) {
                    const script = document.createElement('script');
                    script.id = 'leaflet-js';
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    
                    script.onload = () => {
                        const geocoderScript = document.createElement('script');
                        geocoderScript.id = 'leaflet-geocoder-js';
                        geocoderScript.src = 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js';
                        geocoderScript.onload = () => this.renderMap();
                        document.head.appendChild(geocoderScript);
                    };
                    document.head.appendChild(script);
                } else if (!document.getElementById('leaflet-geocoder-js')) {
                    const geocoderScript = document.createElement('script');
                    geocoderScript.id = 'leaflet-geocoder-js';
                    geocoderScript.src = 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js';
                    geocoderScript.onload = () => this.renderMap();
                    document.head.appendChild(geocoderScript);
                } else {
                    setTimeout(() => this.renderMap(), 200);
                }
            },

            renderMap() {
                if (typeof L === 'undefined' || typeof L.Control.Geocoder === 'undefined') {
                    setTimeout(() => this.renderMap(), 200);
                    return;
                }

                const mapContainer = document.getElementById(this.mapId);
                if (!mapContainer) return;

                if (this.map !== null) {
                    this.map.remove();
                    this.map = null;
                }
                
                if (mapContainer._leaflet_id) {
                    mapContainer._leaflet_id = null;
                    mapContainer.innerHTML = '';
                }

                this.isLoaded = true;
                this.map = L.map(this.mapId);
                
                const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OSM',
                    subdomains: 'abcd',
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

                L.control.layers(baseMaps, null, {position: 'topright'}).addTo(this.map);
                
                L.Control.geocoder({
                    defaultMarkGeocode: true,
                    placeholder: 'Buscar direccion...',
                    errorMessage: 'No se encontro el lugar.'
                }).addTo(this.map);

                if (this.locations.length) {
                    const latlngs = this.locations.map(loc => [loc.lat, loc.lng]);
                    
                    const polyline = L.polyline(latlngs, {
                        color: '#3b82f6', 
                        weight: 0, 
                        opacity: 0,
                        lineCap: 'round',
                        lineJoin: 'round'
                    }).addTo(this.map);
                    
                    const startIcon = L.divIcon({
                        html: '<div class=marker-start></div>',
                        className: '',
                        iconSize: [14, 14],
                        iconAnchor: [7, 7]
                    });

                    const currentIcon = L.divIcon({
                        html: '<div class=marker-current></div>',
                        className: '',
                        iconSize: [18, 18],
                        iconAnchor: [9, 9]
                    });

                    L.marker(latlngs[0], {icon: startIcon})
                     .addTo(this.map)
                     .bindPopup('<b>Inicio de Ruta</b><br>' + new Date(this.locations[0].created_at).toLocaleString());
                    
                    const lastLoc = this.locations[this.locations.length - 1];
                    L.marker(latlngs[latlngs.length - 1], {icon: currentIcon})
                     .addTo(this.map)
                     .bindPopup('<b>Ultima Ubicacion</b><br>' + new Date(lastLoc.created_at).toLocaleString());
                    
                    this.map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
                } else {
                    this.map.setView([15.47, -90.37], 7); 
                }
                
                setTimeout(() => { if(this.map) this.map.invalidateSize(); }, 300);
                setTimeout(() => { if(this.map) this.map.invalidateSize(); }, 800);
                setTimeout(() => { if(this.map) this.map.invalidateSize(); }, 1500);
            }
        }" 
        x-init="initData('{{ base64_encode(json_encode($locations)) }}')"
        class="w-full"
    >
        @if(count($locations) === 0)
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded flex items-center space-x-3">
                <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <strong class="block">No hay datos de ruta disponibles</strong>
                    <span class="text-sm">El camión aún no ha registrado coordenadas GPS en este viaje.</span>
                </div>
            </div>
        @endif

        <div 
            :id="mapId" 
            style="height: 500px; width: 100%; border-radius: 0.5rem; z-index: 10; border: 1px solid #e5e7eb; background-color: #f3f4f6;"
            wire:ignore
        >
            <div x-show="!isLoaded" class="w-full h-full flex items-center justify-center text-gray-500">
                <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Cargando mapa interactivo...
            </div>
        </div>
    </div>
</div>
