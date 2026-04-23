<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 500px;" 
     x-data="{
        map: null,
        isLoaded: false,
        dispatchId: {{ $dispatchId }},
        truckMarker: null,
        routeLine: null,
        allPoints: [],
        stats: { distance: 0, duration: 0 },
        async init() {
            try {
                this.isLoaded = false;
                await this.loadAssets();
                await this.render();
                this.setupEcho();
            } catch (e) {
                console.error('Map Init Fail:', e);
            } finally {
                this.isLoaded = true;
                // Forzar reajuste de tamaño después de un momento
                setTimeout(() => { if(this.map) this.map.invalidateSize(); }, 500);
            }
        },
        async loadAssets() {
            if (window.L) return;
            
            const loadStyle = (url, id) => {
                if (document.getElementById(id)) return Promise.resolve();
                return new Promise(resolve => {
                    const link = document.createElement('link');
                    link.id = id; link.rel = 'stylesheet'; link.href = url; link.onload = resolve; link.onerror = resolve;
                    document.head.appendChild(link);
                });
            };

            const loadScript = (url, id) => {
                if (document.getElementById(id)) return Promise.resolve();
                return new Promise(resolve => {
                    const script = document.createElement('script');
                    script.id = id; script.src = url; script.onload = resolve; script.onerror = resolve;
                    document.head.appendChild(script);
                });
            };

            await loadStyle('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', 'leaflet-css');
            await loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'leaflet-js');
        },
        async render() {
            const el = this.$refs.mapContainer;
            if (!el || typeof L === 'undefined') return;

            if (this.map) {
                this.map.remove();
                this.map = null;
            }

            this.map = L.map(el, { zoomControl: false }).setView([15.47, -90.37], 7);
            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
                attribution: '&copy; OpenStreetMap' 
            }).addTo(this.map);

            let raw = [];
            try { 
                const encodedData = '{{ base64_encode(json_encode($locations)) }}';
                raw = JSON.parse(atob(encodedData)); 
            } catch(e) { console.error('Data Parse Error:', e); }

            this.allPoints = raw.map(l => [parseFloat(l.lat), parseFloat(l.lng)]).filter(p => !isNaN(p[0]) && !isNaN(p[1]));

            if (this.allPoints.length > 0) {
                this.drawRoute();
            }
        },
        drawRoute() {
            if (!this.map || this.allPoints.length === 0) return;

            const lastPoint = this.allPoints[this.allPoints.length - 1];

            const truckIconHtml = `<div style='background:#22c55e;padding:8px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 15px rgba(34,197,94,0.5);'>
                                      <svg style='width:24px;height:24px;color:#fff' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' stroke-width='2'/></svg>
                                   </div>`;

            if (this.truckMarker) this.map.removeLayer(this.truckMarker);
            this.truckMarker = L.marker(lastPoint, { 
                icon: L.divIcon({ html: truckIconHtml, className: '', iconSize: [40, 40], iconAnchor: [20, 20] })
            }).addTo(this.map);

            this.map.setView(lastPoint, this.map.getZoom() > 12 ? this.map.getZoom() : 15);
        },
        setupEcho() {
            if (typeof window.Echo === 'undefined') return;
            window.Echo.channel('dispatch.' + this.dispatchId)
                .listen('.location.updated', (data) => {
                    this.allPoints.push([parseFloat(data.lat), parseFloat(data.lng)]);
                    this.drawRoute();
                });
        }
    }"
>
    <div x-show="!isLoaded" class="absolute inset-0 z-[2000] bg-white dark:bg-gray-900 flex flex-col items-center justify-center">
        <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
        <span class="mt-4 text-[10px] font-black text-gray-400 uppercase tracking-widest animate-pulse">Cargando Mapa...</span>
    </div>

    <div x-ref="mapContainer" class="w-full h-[500px]" style="height: 500px; min-height: 500px;" wire:ignore></div>
</div>
