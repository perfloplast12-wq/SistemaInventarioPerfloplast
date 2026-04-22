<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 500px;" 
2:      x-data="{
3:         map: null,
4:         isLoaded: false,
5:         dispatchId: {{ $dispatchId }},
6:         truckMarker: null,
7:         routeLine: null,
8:         allPoints: [],
9:         stats: { distance: 0, duration: 0 },
10:         async init() {
11:             try {
12:                 await new Promise(r => setTimeout(r, 400));
13:                 await this.loadAssets();
14:                 await this.render();
15:                 this.setupEcho();
16:             } catch (e) {
17:                 console.error('Map Init Fail:', e);
18:                 this.isLoaded = true;
19:             }
20:         },
21:         async loadAssets() {
22:             if (window.L && window.L.Control.Geocoder) return;
23:             
24:             const loadStyle = (url, id) => {
25:                 if (document.getElementById(id)) return Promise.resolve();
26:                 return new Promise(resolve => {
27:                     const link = document.createElement('link');
28:                     link.id = id; link.rel = 'stylesheet'; link.href = url; link.onload = resolve;
29:                     document.head.appendChild(link);
30:                 });
31:             };
32: 
33:             const loadScript = (url, id) => {
34:                 if (document.getElementById(id)) return Promise.resolve();
35:                 return new Promise(resolve => {
36:                     const script = document.createElement('script');
37:                     script.id = id; script.src = url; script.onload = resolve;
38:                     document.head.appendChild(script);
39:                 });
40:             };
41: 
42:             await loadStyle('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', 'leaflet-css');
43:             await loadStyle('https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css', 'geocoder-css');
44:             await loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'leaflet-js');
45:             await loadScript('https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js', 'geocoder-js');
46:         },
47:         async render() {
48:             const el = this.$refs.mapContainer;
49:             if (!el || typeof L === 'undefined') return;
50: 
51:             if (el._leaflet_id) { el._leaflet_id = null; el.innerHTML = ''; }
52: 
53:             this.map = L.map(el, { zoomControl: false }).setView([15.47, -90.37], 7);
54:             L.control.zoom({ position: 'bottomright' }).addTo(this.map);
55: 
56:             const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM', maxZoom: 19 });
57:             const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri', maxZoom: 18 });
58:             
59:             osm.addTo(this.map);
60:             L.control.layers({ 'Calle': osm, 'Satelite': satellite }, null, { position: 'topright' }).addTo(this.map);
61: 
62:             let raw = [];
63:             try { raw = JSON.parse(atob('{{ base64_encode(json_encode($locations)) }}')); } catch(e) {}
64:             const locations = raw.filter(l => l.lat > 10 && l.lat < 22 && l.lng > -100 && l.lng < -80);
65:             this.allPoints = (locations.length > 0 ? locations : raw).map(l => [parseFloat(l.lat), parseFloat(l.lng)]);
66: 
67:             if (this.allPoints.length > 0) {
68:                 await this.drawRoute();
69:             }
70: 
71:             this.isLoaded = true;
72:             for(let t of [100, 500, 1000]) setTimeout(() => this.map.invalidateSize(), t);
73:         },
74:         async drawRoute() {
75:             const pts = this.allPoints;
76:             let routeCoords = pts;
77:             
78:             if (pts.length >= 2) {
79:                 try {
80:                     const query = pts.map(p => p[1] + ',' + p[0]).join(';');
81:                     const r = await fetch('https://router.project-osrm.org/route/v1/driving/' + query + '?overview=full&geometries=geojson');
82:                     if (r.ok) {
83:                         const d = await r.json();
84:                         if (d.routes && d.routes[0]) {
85:                             routeCoords = d.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
86:                             this.stats.distance = (d.routes[0].distance / 1000).toFixed(1);
87:                             this.stats.duration = Math.round(d.routes[0].duration / 60);
88:                         }
89:                     }
90:                 } catch(e) {}
91:             }
92: 
93:             if (this.routeLine) this.map.removeLayer(this.routeLine);
94:             this.routeLine = L.polyline(routeCoords, { color: '#10b981', weight: 6, opacity: 0.85, lineJoin: 'round' }).addTo(this.map);
95: 
96:             const houseIconHtml = `<div style='display:flex;flex-direction:column;align-items:center;'>
97:                                     <div style='background:#1e293b;padding:8px;border-radius:8px;border:2px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,0.3);color:#fff;'>
98:                                       <svg style='width:20px;height:20px;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' stroke-width='2'/></svg>
99:                                     </div>
100:                                     <div style='width:10px;height:10px;background:#1e293b;transform:rotate(45deg);margin-top:-6px;border-right:2px solid #fff;border-bottom:2px solid #fff;'></div>
101:                                   </div>`;
102: 
103:             const truckIconHtml = `<div style='display:flex;flex-direction:column;align-items:center;position:relative;'>
104:                                     <div style='position:absolute;width:60px;height:60px;background:rgba(34,197,94,0.3);border-radius:50%;top:-5px;animation:pulse-marker 2s infinite;'></div>
105:                                     <div style='background:#22c55e;padding:10px;border-radius:12px;border:3px solid #fff;box-shadow:0 10px 20px rgba(0,0,0,0.4);color:#fff;position:relative;z-index:2;'>
106:                                       <svg style='width:28px;height:28px;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' stroke-width='2'/></svg>
107:                                     </div>
108:                                     <div style='width:12px;height:12px;background:#22c55e;transform:rotate(45deg);margin-top:-7px;border-right:3px solid #fff;border-bottom:3px solid #fff;position:relative;z-index:1;'></div>
109:                                   </div>`;
110: 
111:             if (pts.length > 0) {
112:                 L.marker(pts[0], { icon: L.divIcon({ html: houseIconHtml, className: '', iconSize: [36, 42], iconAnchor: [18, 42] }) }).addTo(this.map);
113:                 
114:                 if (this.truckMarker) this.map.removeLayer(this.truckMarker);
115:                 this.truckMarker = L.marker(pts[pts.length - 1], { 
116:                     icon: L.divIcon({ html: truckIconHtml, className: '', iconSize: [48, 56], iconAnchor: [24, 56] }),
117:                     zIndexOffset: 1000 
118:                 }).addTo(this.map).bindPopup('<b>Camion en ruta</b>');
119: 
120:                 this.map.fitBounds(this.routeLine.getBounds(), { padding: [60, 60] });
121:             }
122:         },
123:         setupEcho() {
124:             if (typeof window.Echo === 'undefined') {
125:                 console.warn('Laravel Echo no detectado. Reintentando en 2s...');
126:                 setTimeout(() => this.setupEcho(), 2000);
127:                 return;
128:             }
129: 
130:             console.log('Conectando a canal dispatch.' + this.dispatchId);
131:             window.Echo.channel('dispatch.' + this.dispatchId)
132:                 .listen('.location.updated', (data) => {
133:                     console.log('GPS Live Update:', data);
134:                     this.allPoints.push([parseFloat(data.lat), parseFloat(data.lng)]);
135:                     this.drawRoute();
136:                     
137:                     // Notificación visual de actualización
138:                     if (window.Filament) {
139:                         // window.Filament.notify('success', 'Ubicación actualizada en vivo');
140:                     }
141:                 });
142:         }
143:     }"
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
