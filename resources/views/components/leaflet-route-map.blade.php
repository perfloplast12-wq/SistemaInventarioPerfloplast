<div class="relative w-full rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 shadow-xl" style="min-height: 600px;" 
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

        isValidGuatemalaCoord(lat, lng) {
            // Rango aproximado de Guatemala para evitar puntos en el océano o fuera del país
            return lat > 13.5 && lat < 18.5 && lng > -92.5 && lng < -88.0;
        },

        async render() {
            const el = this.$refs.mapContainer;
            if (!el || typeof L === 'undefined') return;

            if (this.map) {
                this.map.remove();
                this.map = null;
            }

            // Vista inicial en Guatemala por defecto si no hay puntos
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

            // Filtrar puntos basura (0,0) o fuera de Guatemala
            this.allPoints = raw.map(l => [parseFloat(l.lat), parseFloat(l.lng)])
                                .filter(p => !isNaN(p[0]) && !isNaN(p[1]) && this.isValidGuatemalaCoord(p[0], p[1]));

            if (this.allPoints.length > 0) {
                this.drawRoute(true); // true para forzar zoom inicial al último punto
            }
        },

        drawRoute(forceFocus = false) {
            if (!this.map || this.allPoints.length === 0) return;

            const lastPoint = this.allPoints[this.allPoints.length - 1];

            // Marcador personalizado "Camión" con efecto de pulso
            const truckIconHtml = `<div class='relative flex items-center justify-center'>
                                      <div class='absolute w-12 h-12 bg-emerald-500 rounded-full opacity-25 animate-ping'></div>
                                      <div class='relative bg-emerald-600 p-3 rounded-2xl border-2 border-white shadow-2xl'>
                                          <svg class='w-6 h-6 text-white' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' stroke-width='2'/></svg>
                                      </div>
                                   </div>`;

            if (this.truckMarker) this.map.removeLayer(this.truckMarker);
            
            this.truckMarker = L.marker(lastPoint, { 
                icon: L.divIcon({ html: truckIconHtml, className: '', iconSize: [48, 48], iconAnchor: [24, 24] })
            }).addTo(this.map);

            // Trazar línea de ruta (azul suave)
            if (this.routeLine) this.map.removeLayer(this.routeLine);
            this.routeLine = L.polyline(this.allPoints, {
                color: '#3b82f6',
                weight: 4,
                opacity: 0.6,
                lineJoin: 'round'
            }).addTo(this.map);

            // Si es forzado o el zoom es muy lejano, nos acercamos al detalle de calle
            if (forceFocus) {
                this.map.setView(lastPoint, 17); // Zoom nivel calle exacto
            } else {
                // Si ya estamos viendo, solo actualizamos suavemente si se mueve
                this.map.panTo(lastPoint);
            }
        },

        setupEcho() {
            if (typeof window.Echo === 'undefined') return;
            window.Echo.channel('dispatch.' + this.dispatchId)
                .listen('.location.updated', (data) => {
                    const lat = parseFloat(data.lat);
                    const lng = parseFloat(data.lng);
                    
                    // Validar punto entrante antes de añadirlo
                    if (!isNaN(lat) && !isNaN(lng) && this.isValidGuatemalaCoord(lat, lng)) {
                        this.allPoints.push([lat, lng]);
                        this.drawRoute();
                    }
                });
        }
    }"
>
    <div x-show="!isLoaded" class="absolute inset-0 z-[2000] bg-white dark:bg-gray-900 flex flex-col items-center justify-center">
        <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
        <span class="mt-4 text-[10px] font-black text-gray-400 uppercase tracking-widest animate-pulse">Sincronizando Ruta de Despacho...</span>
    </div>

    <div x-ref="mapContainer" class="w-full h-[600px]" style="height: 600px; min-height: 600px;" wire:ignore></div>
    
    {{-- Panel Flotante de Info --}}
    <div class="absolute top-4 left-4 z-[1000] bg-white/90 dark:bg-gray-800/90 backdrop-blur-md p-4 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl max-w-xs">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-ping"></div>
            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Estado en Tiempo Real</span>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-300 font-medium">
            Mapa optimizado con enfoque automático en la posición actual del piloto.
        </p>
    </div>
</div>
