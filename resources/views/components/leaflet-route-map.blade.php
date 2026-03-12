<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 500px;" 
     x-data="{
        map: null,
        isLoaded: false,
        stats: { distance: 0, duration: 0 },
        async init() {
            try {
                // Pequeña espera para que el DOM se asiente (especialmente en modales)
                await new Promise(r => setTimeout(r, 300));
                await this.loadAssets();
                await this.render();
            } catch (e) {
                console.error('Map Init Detail:', e);
                this.isLoaded = true; // Quitar el cargando aunque falle
            }
        },
        async loadAssets() {
            if (window.L && window.L.Control.Geocoder) return;
            
            const loadStyle = (url, id) => {
                if (document.getElementById(id)) return Promise.resolve();
                return new Promise(resolve => {
                    const link = document.createElement('link');
                    link.id = id;
                    link.rel = 'stylesheet';
                    link.href = url;
                    link.onload = resolve;
                    document.head.appendChild(link);
                });
            };

            const loadScript = (url, id) => {
                if (document.getElementById(id)) return Promise.resolve();
                return new Promise(resolve => {
                    const script = document.createElement('script');
                    script.id = id;
                    script.src = url;
                    script.onload = resolve;
                    document.head.appendChild(script);
                });
            };

            await loadStyle('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', 'leaflet-css');
            await loadStyle('https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css', 'geocoder-css');
            await loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'leaflet-js');
            await loadScript('https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js', 'geocoder-js');
        },
        async render() {
            const el = this.$refs.mapContainer;
            if (!el || typeof L === 'undefined') return;

            // Limpieza absoluta
            if (el._leaflet_id) {
                el._leaflet_id = null;
                el.innerHTML = '';
            }

            // Inicializar mapa con centro por defecto en Guatemala
            this.map = L.map(el, { 
                zoomControl: false,
                fadeAnimation: true,
                markerZoomAnimation: true
            }).setView([15.47, -90.37], 7);

            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            // Capas con fallback de OpenStreetMap (más fiable)
            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OSM',
                maxZoom: 19
            });
            
            const carto = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CartoDB',
                maxZoom: 20
            });

            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; Esri',
                maxZoom: 18
            });

            // Default
            osm.addTo(this.map);

            L.control.layers({ 
                'Calle (OSM)': osm,
                'Premium (Carto)': carto,
                'Satelite (Esri)': satellite 
            }, null, { position: 'topright' }).addTo(this.map);

            // Procesar datos
            let raw = [];
            try { 
                const b64 = '{{ base64_encode(json_encode($locations)) }}';
                raw = JSON.parse(atob(b64)); 
            } catch(e) { console.error('Data decode fail'); }

            const filtered = raw.filter(l => {
                const lat = parseFloat(l.lat);
                const lng = parseFloat(l.lng);
                return lat > 10 && lat < 22 && lng > -100 && lng < -80;
            });

            const locations = filtered.length > 0 ? filtered : raw;

            if (locations.length > 0) {
                const pts = locations.map(l => [parseFloat(l.lat), parseFloat(l.lng)]);
                
                // OSRM para ruteo
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
                    } catch(e) {}
                }

                // Dibujar Línea
                const line = L.polyline(routeCoords, {
                    color: '#10b981',
                    weight: 6,
                    opacity: 0.85,
                    lineJoin: 'round'
                }).addTo(this.map);

                // Iconos
                const houseIcon = L.divIcon({
                    html: `<div class='bg-slate-800 p-2 rounded-lg border-2 border-white shadow-lg text-white'><svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' stroke-width='2'/></svg></div>`,
                    className: '', iconSize: [32, 32], iconAnchor: [16, 16]
                });

                const truckIcon = L.divIcon({
                    html: `<div class='bg-emerald-500 p-2 rounded-full border-2 border-white shadow-xl text-white animate-pulse ring-4 ring-emerald-500/20'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' stroke-width='2'/></svg></div>`,
                    className: '', iconSize: [40, 40], iconAnchor: [20, 20]
                });

                L.marker(pts[0], { icon: houseIcon }).addTo(this.map);
                L.marker(pts[pts.length - 1], { icon: truckIcon }).addTo(this.map);

                this.map.fitBounds(line.getBounds(), { padding: [50, 50] });
            }

            this.isLoaded = true;
            // Forzar detección de tamaño en varios intervalos
            for(let t of [100, 300, 1000]) {
                setTimeout(() => this.map.invalidateSize(), t);
            }
        }
    }"
>
    {{-- Stats Overlay --}}
    <div x-show="isLoaded && stats.distance > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute top-4 left-4 z-[1000] bg-white/95 dark:bg-gray-800/95 backdrop-blur shadow-2xl rounded-xl border border-white/20 p-4 flex gap-6 shrink-0">
        <div class="flex flex-col">
            <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400">Distancia</p>
            <p class="text-lg font-black text-slate-900 dark:text-white" x-text="stats.distance + ' km'"></p>
        </div>
        <div class="w-px h-10 bg-gray-200 dark:bg-gray-700"></div>
        <div class="flex flex-col">
            <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400">Tiempo Est.</p>
            <p class="text-lg font-black text-slate-900 dark:text-white" x-text="stats.duration + ' min'"></p>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="!isLoaded" class="absolute inset-0 z-[2000] bg-white dark:bg-gray-900 flex flex-col items-center justify-center">
        <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
        <p class="mt-4 text-xs font-bold text-gray-400 uppercase tracking-tighter">Procesando Mapa...</p>
    </div>

    {{-- Map Container --}}
    <div x-ref="mapContainer" id="map-{{ md5(uniqid()) }}" class="w-full h-[500px]" style="height: 500px; min-height: 500px;" wire:ignore></div>
</div>
