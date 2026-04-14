<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 500px;" 
     x-data="{
        map: null,
        isLoaded: false,
        stats: { distance: 0, duration: 0 },
        async init() {
            try {
                await new Promise(r => setTimeout(r, 400));
                await this.loadAssets();
                await this.render();
            } catch (e) {
                console.error('Map Init Fail:', e);
                this.isLoaded = true;
            }
        },
        async loadAssets() {
            if (window.L && window.L.Control.Geocoder) return;
            
            const loadStyle = (url, id) => {
                if (document.getElementById(id)) return Promise.resolve();
                return new Promise(resolve => {
                    const link = document.createElement('link');
                    link.id = id; link.rel = 'stylesheet'; link.href = url; link.onload = resolve;
                    document.head.appendChild(link);
                });
            };

            const loadScript = (url, id) => {
                if (document.getElementById(id)) return Promise.resolve();
                return new Promise(resolve => {
                    const script = document.createElement('script');
                    script.id = id; script.src = url; script.onload = resolve;
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

            if (el._leaflet_id) { el._leaflet_id = null; el.innerHTML = ''; }

            this.map = L.map(el, { zoomControl: false }).setView([15.47, -90.37], 7);
            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM', maxZoom: 19 });
            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri', maxZoom: 18 });
            
            osm.addTo(this.map);
            L.control.layers({ 'Calle': osm, 'Satelite': satellite }, null, { position: 'topright' }).addTo(this.map);

            let raw = [];
            try { raw = JSON.parse(atob('{{ base64_encode(json_encode($locations)) }}')); } catch(e) {}
            const locations = raw.filter(l => l.lat > 10 && l.lat < 22 && l.lng > -100 && l.lng < -80);
            const pts = (locations.length > 0 ? locations : raw).map(l => [parseFloat(l.lat), parseFloat(l.lng)]);

            if (pts.length > 0) {
                // Ruteo
                let routeCoords = pts;
                if (pts.length >= 2) {
                    try {
                        const query = pts.map(p => p[1] + ',' + p[0]).join(';');
                        const r = await fetch('https://router.project-osrm.org/route/v1/driving/' + query + '?overview=full&geometries=geojson');
                        if (r.ok) {
                            const d = await r.json();
                            if (d.routes && d.routes[0]) {
                                routeCoords = d.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                                this.stats.distance = (d.routes[0].distance / 1000).toFixed(1);
                                this.stats.duration = Math.round(d.routes[0].duration / 60);
                            }
                        }
                    } catch(e) {}
                }

                const line = L.polyline(routeCoords, { color: '#10b981', weight: 6, opacity: 0.85, lineJoin: 'round' }).addTo(this.map);

                // Iconos con Estilo Inline Garantizado
                const houseIconHtml = `<div style='display:flex;flex-direction:column;align-items:center;'>
                                        <div style='background:#1e293b;padding:8px;border-radius:8px;border:2px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,0.3);color:#fff;'>
                                          <svg style='width:20px;height:20px;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' stroke-width='2'/></svg>
                                        </div>
                                        <div style='width:10px;height:10px;background:#1e293b;transform:rotate(45deg);margin-top:-6px;border-right:2px solid #fff;border-bottom:2px solid #fff;'></div>
                                      </div>`;

                const truckIconHtml = `<div style='display:flex;flex-direction:column;align-items:center;position:relative;'>
                                        <div style='position:absolute;width:60px;height:60px;background:rgba(34,197,94,0.3);border-radius:50%;top:-5px;animation:pulse-marker 2s infinite;'></div>
                                        <div style='background:#22c55e;padding:10px;border-radius:12px;border:3px solid #fff;box-shadow:0 10px 20px rgba(0,0,0,0.4);color:#fff;position:relative;z-index:2;'>
                                          <svg style='width:28px;height:28px;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' stroke-width='2'/></svg>
                                        </div>
                                        <div style='width:12px;height:12px;background:#22c55e;transform:rotate(45deg);margin-top:-7px;border-right:3px solid #fff;border-bottom:3px solid #fff;position:relative;z-index:1;'></div>
                                      </div>`;

                L.marker(pts[0], { icon: L.divIcon({ html: houseIconHtml, className: '', iconSize: [36, 42], iconAnchor: [18, 42] }) }).addTo(this.map);
                L.marker(pts[pts.length - 1], { 
                    icon: L.divIcon({ html: truckIconHtml, className: '', iconSize: [48, 56], iconAnchor: [24, 56] }),
                    zIndexOffset: 1000 
                }).addTo(this.map).bindPopup('<b>Camion en ruta</b>');

                this.map.fitBounds(line.getBounds(), { padding: [60, 60] });
            }

            this.isLoaded = true;
            for(let t of [100, 500, 1000]) setTimeout(() => this.map.invalidateSize(), t);
        }
    }"
>
    <style>
        @keyframes pulse-marker { 0% { transform: scale(0.4); opacity: 1; } 100% { transform: scale(1.1); opacity: 0; } }
        .leaflet-container { font-family: inherit !important; background: #f8fafc !important; }
    </style>

    {{-- Stats Overlay --}}
    <div x-show="isLoaded && stats.distance > 0" x-transition 
         class="absolute top-4 left-4 z-[1000] bg-white/95 dark:bg-gray-800/95 backdrop-blur shadow-2xl rounded-xl border border-white/20 p-4 flex gap-6">
        <div class="flex flex-col">
            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Distancia</span>
            <span class="text-lg font-black text-slate-900 dark:text-white" x-text="stats.distance + ' km'"></span>
        </div>
        <div class="w-px h-10 bg-gray-200 dark:bg-gray-700"></div>
        <div class="flex flex-col">
            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Tiempo Est.</span>
            <span class="text-lg font-black text-slate-900 dark:text-white" x-text="stats.duration + ' min'"></span>
        </div>
    </div>

    {{-- Loading Shade --}}
    <div x-show="!isLoaded" class="absolute inset-0 z-[2000] bg-white dark:bg-gray-900 flex flex-col items-center justify-center">
        <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
        <span class="mt-4 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] animate-pulse">Optimizando Mapa...</span>
    </div>

    {{-- Map Container --}}
    <div x-ref="mapContainer" class="w-full h-[500px]" style="height: 500px;" wire:ignore></div>
</div>
