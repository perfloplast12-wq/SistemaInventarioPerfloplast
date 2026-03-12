<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900" style="min-height: 500px;" 
     x-data="{
        map: null,
        isLoaded: false,
        stats: { distance: 0, duration: 0 },
        init() {
            this.loadAssets().then(() => this.render());
        },
        async loadAssets() {
            if (window.L && window.L.Control.Geocoder) return;
            return new Promise((resolve) => {
                if (!document.getElementById('leaflet-css')) {
                    const link = document.createElement('link');
                    link.id = 'leaflet-css';
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);
                }
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = () => {
                    const gScript = document.createElement('script');
                    gScript.src = 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js';
                    gScript.onload = () => resolve();
                    document.head.appendChild(gScript);
                };
                document.head.appendChild(script);
            });
        },
        async render() {
            const el = this.$refs.mapContainer;
            if (!el || typeof L === 'undefined') return;
            if (el._leaflet_id) { el._leaflet_id = null; el.innerHTML = ''; }

            this.map = L.map(el, { zoomControl: false }).setView([15.47, -90.37], 7);
            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { attribution: '&copy; CartoDB', maxZoom: 20 });
            const osmStreet = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM', maxZoom: 19 });
            const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri', maxZoom: 18 });

            cartoLight.addTo(this.map);
            L.control.layers({ 'Mapa Claro': cartoLight, 'Calles': osmStreet, 'Satelite': esriSatellite }, null, { position: 'topright' }).addTo(this.map);

            let raw = [];
            try { raw = JSON.parse(atob('{{ base64_encode(json_encode($locations)) }}')); } catch(e) {}
            const locations = raw.filter(l => l.lat > 10 && l.lat < 22 && l.lng > -100 && l.lng < -80);
            const pts = (locations.length > 0 ? locations : raw).map(l => [parseFloat(l.lat), parseFloat(l.lng)]);

            if (pts.length > 0) {
                // Ruteo por carretera (OSRM)
                let routeCoords = pts;
                if (pts.length >= 2) {
                    try {
                        const query = pts.map(p => p[1] + ',' + p[0]).join(';');
                        const response = await fetch('https://router.project-osrm.org/route/v1/driving/' + query + '?overview=full&geometries=geojson');
                        if (response.ok) {
                            const data = await response.json();
                            if (data.routes && data.routes[0]) {
                                routeCoords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                                this.stats.distance = (data.routes[0].distance / 1000).toFixed(1);
                                this.stats.duration = Math.round(data.routes[0].duration / 60);
                            }
                        }
                    } catch(e) { console.warn('Routing API failed, using straight lines'); }
                }

                // Línea de ruta con estilo Premium
                const routeLine = L.polyline(routeCoords, {
                    color: '#10b981',
                    weight: 6,
                    opacity: 0.9,
                    lineJoin: 'round',
                    lineCap: 'round'
                }).addTo(this.map);

                // Iconos Súper Premium
                const warehouseIcon = L.divIcon({
                    html: `<div class='bg-slate-800 p-2 rounded-full border-2 border-white shadow-lg text-white'>
                             <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'></path></svg>
                           </div>`,
                    className: '', iconSize: [32, 32], iconAnchor: [16, 16]
                });

                const truckIcon = L.divIcon({
                    html: `<div class='bg-emerald-500 p-2 rounded-full border-2 border-white shadow-xl text-white animate-bounce-subtle ring-4 ring-emerald-500/30'>
                             <svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'></path></svg>
                           </div>`,
                    className: '', iconSize: [40, 40], iconAnchor: [20, 20]
                });

                L.marker(pts[0], { icon: warehouseIcon }).addTo(this.map).bindPopup('Punto de Partida');
                L.marker(pts[pts.length - 1], { icon: truckIcon }).addTo(this.map).bindPopup('<b>Ubicación Actual</b><br>Camión en ruta');

                this.map.fitBounds(routeLine.getBounds(), { padding: [60, 60] });
            }

            this.isLoaded = true;
            setTimeout(() => this.map.invalidateSize(), 400);
        }
    }"
>
    <style>
        @keyframes bounce-subtle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        .animate-bounce-subtle { animation: bounce-subtle 2s infinite ease-in-out; }
        .leaflet-container { font-family: inherit; }
        .leaflet-bar { border: none !important; box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important; }
        .leaflet-bar a { background: white !important; color: #1e293b !important; }
    </style>

    {{-- Stats Overlay --}}
    <div x-show="isLoaded && stats.distance > 0" 
         x-transition 
         class="absolute top-4 left-4 z-[1000] bg-white/90 dark:bg-gray-800/90 backdrop-blur-md p-3 rounded-lg shadow-xl border border-white/20 flex items-center gap-4">
        <div class="flex flex-col">
            <span class="text-[10px] uppercase font-bold text-gray-500">Distancia</span>
            <span class="text-sm font-black text-slate-800 dark:text-white" x-text="stats.distance + ' km'"></span>
        </div>
        <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
        <div class="flex flex-col">
            <span class="text-[10px] uppercase font-bold text-gray-500">Tiempo Est.</span>
            <span class="text-sm font-black text-slate-800 dark:text-white" x-text="stats.duration + ' min'"></span>
        </div>
    </div>

    {{-- Loading Shade --}}
    <div x-show="!isLoaded" class="absolute inset-0 z-[2000] bg-white dark:bg-gray-900 flex flex-col items-center justify-center">
        <div class="relative w-16 h-16 mb-4">
            <div class="absolute inset-0 border-4 border-primary-500/20 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-primary-500 rounded-full border-t-transparent animate-spin"></div>
        </div>
        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest animate-pulse">Optimizando Ruta...</span>
    </div>

    {{-- Map Container --}}
    <div x-ref="mapContainer" class="w-full h-[500px]" wire:ignore></div>
</div>
