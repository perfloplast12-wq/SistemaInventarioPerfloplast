<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 550px;" 
     x-data="{
        map: null,
        isLoaded: false,
        dispatchId: {{ $dispatchId }},
        truckMarker: null,
        routeLine: null,
        allPoints: [],
        
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

        isValidCoord(lat, lng) {
            return lat > 13.0 && lat < 19.0 && lng > -93.0 && lng < -87.0;
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

            this.allPoints = raw.map(l => [parseFloat(l.lat), parseFloat(l.lng)])
                                .filter(p => !isNaN(p[0]) && !isNaN(p[1]) && this.isValidCoord(p[0], p[1]));

            if (this.allPoints.length > 0) {
                this.drawRoute(true);
            }
        },

        drawRoute(forceFocus = false) {
            if (!this.map || this.allPoints.length === 0) return;

            const lastPoint = this.allPoints[this.allPoints.length - 1];

            if (this.truckMarker) this.map.removeLayer(this.truckMarker);
            
            // Marcador simple pero efectivo (Círculo esmeralda con borde blanco)
            this.truckMarker = L.circleMarker(lastPoint, {
                radius: 10,
                fillColor: '#10b981',
                color: '#ffffff',
                weight: 3,
                opacity: 1,
                fillOpacity: 1
            }).addTo(this.map);

            if (this.routeLine) this.map.removeLayer(this.routeLine);
            this.routeLine = L.polyline(this.allPoints, {
                color: '#3b82f6',
                weight: 5,
                opacity: 0.5
            }).addTo(this.map);

            if (forceFocus) {
                this.map.setView(lastPoint, 16); 
            } else {
                this.map.panTo(lastPoint);
            }
        },

        setupEcho() {
            if (typeof window.Echo === 'undefined') return;
            window.Echo.channel('dispatch.' + this.dispatchId)
                .listen('.location.updated', (data) => {
                    const lat = parseFloat(data.lat);
                    const lng = parseFloat(data.lng);
                    if (!isNaN(lat) && !isNaN(lng) && this.isValidCoord(lat, lng)) {
                        this.allPoints.push([lat, lng]);
                        this.drawRoute();
                    }
                });
        }
    }"
>
    <div x-show="!isLoaded" class="absolute inset-0 z-[2000] bg-white dark:bg-gray-900 flex flex-col items-center justify-center">
        <div class="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div x-ref="mapContainer" class="w-full h-[550px]" style="height: 550px;" wire:ignore></div>
</div>
