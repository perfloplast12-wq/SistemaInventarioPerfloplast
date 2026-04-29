<div class="relative w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800" style="min-height: 550px;" 
     x-data="leafletRouteMap({
        dispatchId: {{ $dispatchId }},
        dispatchNumber: '{{ $dispatchNumber ?? '' }}',
        driverName: '{{ $driverName ?? 'Sin asignar' }}',
        truckName: '{{ $truckName ?? 'Sin asignar' }}',
        routeName: '{{ $routeName ?? 'Sin ruta' }}',
        dispatchStatus: '{{ $dispatchStatus ?? 'pending' }}',
        locations: '{{ base64_encode(json_encode($locations)) }}'
     })"
>
    <style>
        @keyframes truck-pulse {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.2); opacity: 0; }
        }
    </style>

    <div x-show="!isLoaded" class="absolute inset-0 z-[2000] bg-white dark:bg-gray-900 flex flex-col items-center justify-center">
        <div class="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div x-ref="mapContainer" class="w-full h-[550px]" style="height: 550px;" wire:ignore></div>

    <script>
        (function() {
            const registerMap = () => {
                if (typeof Alpine === 'undefined') return;
                
                try {
                    if (Alpine.data('leafletRouteMap')) return;
                } catch (e) {}

                Alpine.data('leafletRouteMap', (config) => ({
                    map: null,
                    isLoaded: false,
                    dispatchId: config.dispatchId,
                    dispatchNumber: config.dispatchNumber,
                    driverName: config.driverName,
                    truckName: config.truckName,
                    routeName: config.routeName,
                    dispatchStatus: config.dispatchStatus,
                    truckMarker: null,
                    allPoints: [],
                    isOnline: true,
                    lastSignal: null,
                    
                    async init() {
                        try {
                            this.isLoaded = false;
                            this.isOnline = true;
                            await this.loadAssets();
                            await this.render(config.locations);
                            this.startEcho();
                        } catch (e) {
                            console.error('Map Init Fail:', e);
                        } finally {
                            this.isLoaded = true;
                            [100, 300, 600, 1200].forEach(ms => {
                                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, ms);
                            });
                        }
                    },
                    
                    async loadAssets() {
                        if (window.L) return;
                        
                        await new Promise((resolve) => {
                            const css = document.createElement('link');
                            css.rel = 'stylesheet';
                            css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                            css.onload = resolve;
                            css.onerror = resolve;
                            document.head.appendChild(css);
                        });

                        await new Promise((resolve) => {
                            const js = document.createElement('script');
                            js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                            js.onload = resolve;
                            js.onerror = resolve;
                            document.head.appendChild(js);
                        });

                        // Esperar a que L esté disponible
                        let wait = 0;
                        while (typeof window.L === 'undefined' && wait < 50) {
                            await new Promise(r => setTimeout(r, 60));
                            wait++;
                        }
                    },

                    isValidCoord(lat, lng) {
                        return lat > 13.0 && lat < 19.0 && lng > -93.0 && lng < -87.0;
                    },

                    getStatusLabel(status) {
                        return {'pending':'Pendiente','in_progress':'En Ruta','completed':'Completado','delivered':'Entregado'}[status] || status;
                    },

                    getStatusColor(status) {
                        return {'pending':'#9ca3af','in_progress':'#10b981','completed':'#3b82f6','delivered':'#8b5cf6'}[status] || '#6b7280';
                    },

                    createTruckIcon() {
                        const baseColor = this.getStatusColor(this.dispatchStatus);
                        const color = this.isOnline ? baseColor : '#6b7280';
                        const showPulse = this.dispatchStatus === 'in_progress' && this.isOnline;
                        const pulse = showPulse ? '<div style="position:absolute;width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,0.3);animation:truck-pulse 2s ease-out infinite;"></div>' : '';
                        const dotColor = showPulse ? '#22c55e' : '#9ca3af';
                        const label = this.isOnline ? this.truckName : this.truckName + ' (Sin señal)';
                        
                        return L.divIcon({
                            className: 'custom-div-icon',
                            html: `<div style="display:flex;flex-direction:column;align-items:center;transform:translateY(-50%);opacity:${this.isOnline?'1':'0.7'};">
                                <div style="position:relative;">
                                    ${pulse}
                                    <div style="width:44px;height:44px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.4);border:3px solid white;background:linear-gradient(135deg,${color},${color}dd);">
                                        <svg style="width:22px;height:22px;color:white;transform:rotate(45deg);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1" stroke-width="2"/>
                                        </svg>
                                    </div>
                                    <div style="position:absolute;top:-2px;right:-2px;width:12px;height:12px;border-radius:50%;border:2px solid white;z-index:10;background:${dotColor};"></div>
                                </div>
                                <div style="margin-top:6px;padding:3px 8px;background:rgba(0,0,0,0.8);color:white;font-size:10px;font-weight:700;border-radius:10px;white-space:nowrap;">${label}</div>
                            </div>`,
                            iconSize: [54, 72], iconAnchor: [27, 54], popupAnchor: [0, -54]
                        });
                    },

                    createPopupContent(lat, lng) {
                        const sc = this.getStatusColor(this.dispatchStatus);
                        const isActive = this.dispatchStatus === 'in_progress';
                        const badgeBg = this.isOnline ? (isActive ? '#dcfce7' : '#f3f4f6') : '#fee2e2';
                        const badgeTxt = this.isOnline ? (isActive ? '#15803d' : '#6b7280') : '#991b1b';
                        const label = this.isOnline ? this.getStatusLabel(this.dispatchStatus) : 'Sin señal GPS';
                        const dotC = this.isOnline ? sc : '#ef4444';
                        const iconBg = this.isOnline ? sc : '#6b7280';
                        
                        return `<div style="min-width:240px;padding:12px;font-family:-apple-system,sans-serif;">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,${iconBg},${iconBg}bb);display:flex;align-items:center;justify-content:center;">
                                    <svg style="width:20px;height:20px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1" stroke-width="2"/></svg>
                                </div>
                                <div>
                                    <p style="font-weight:700;font-size:14px;color:#111827;margin:0;">${this.dispatchNumber}</p>
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:10px;font-weight:600;background:${badgeBg};color:${badgeTxt};">
                                        <span style="width:6px;height:6px;border-radius:50%;background:${dotC};"></span>
                                        ${label}
                                    </span>
                                </div>
                            </div>
                            <div style="border-top:1px solid #e5e7eb;padding-top:8px;display:grid;gap:4px;font-size:12px;color:#6b7280;">
                                <div>👤 <strong>Piloto:</strong> ${this.driverName}</div>
                                <div>🚛 <strong>Camión:</strong> ${this.truckName}</div>
                                <div>⏲️ <strong>Última señal:</strong> ${this.lastSignal || 'Reciente'}</div>
                                <div>📍 <strong>Posición:</strong> ${lat.toFixed(5)}, ${lng.toFixed(5)}</div>
                            </div>
                        </div>`;
                    },

                    async render(encodedLocations) {
                        const el = this.$refs.mapContainer;
                        if (!el || typeof window.L === 'undefined') return;
                        
                        if (this.map) { this.map.remove(); this.map = null; }
                        
                        this.map = L.map(el, { zoomControl: false }).setView([15.47, -90.37], 7);
                        L.control.zoom({ position: 'bottomright' }).addTo(this.map);
                        L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                            attribution: '&copy; Google Maps', maxZoom: 20
                        }).addTo(this.map);

                        let raw = [];
                        try { raw = JSON.parse(atob(encodedLocations)); } catch(e) {}

                        // Filtrar coordenadas válidas (excluir las señales de desconexión con accuracy=-1)
                        this.allPoints = raw
                            .filter(l => l.accuracy != -1)
                            .map(l => [parseFloat(l.lat), parseFloat(l.lng)])
                            .filter(p => !isNaN(p[0]) && !isNaN(p[1]) && this.isValidCoord(p[0], p[1]));
                        
                        // Detectar si la última señal fue de desconexión
                        if (raw.length > 0) {
                            const last = raw[raw.length - 1];
                            if (last.accuracy == -1) {
                                this.isOnline = false;
                            }
                        }
                        
                        // SIEMPRE mostrar el marcador en la última posición válida
                        if (this.allPoints.length > 0) {
                            this.drawPosition(true);
                        }
                    },

                    drawPosition(forceFocus = false) {
                        if (!this.map || this.allPoints.length === 0) return;
                        const lastPoint = this.allPoints[this.allPoints.length - 1];
                        if (this.truckMarker) this.map.removeLayer(this.truckMarker);
                        this.truckMarker = L.marker(lastPoint, { icon: this.createTruckIcon() })
                            .addTo(this.map)
                            .bindPopup(this.createPopupContent(lastPoint[0], lastPoint[1]), {
                                maxWidth: 320, closeOnClick: false, autoClose: false
                            });
                        if (forceFocus) this.map.setView(lastPoint, 17);
                        else this.map.panTo(lastPoint);
                    },

                    startEcho() {
                        if (typeof window.Echo === 'undefined') {
                            // Reintentar hasta que Echo esté disponible
                            let tries = 0;
                            const retry = setInterval(() => {
                                tries++;
                                if (typeof window.Echo !== 'undefined') {
                                    clearInterval(retry);
                                    this.connectEcho();
                                }
                                if (tries > 15) clearInterval(retry);
                            }, 1000);
                            return;
                        }
                        this.connectEcho();
                    },

                    connectEcho() {
                        window.Echo.channel('dispatch.' + this.dispatchId)
                            .listen('.location.updated', (data) => {
                                this.isOnline = !data.is_offline;
                                if (data.timestamp) {
                                    this.lastSignal = new Date(data.timestamp).toLocaleTimeString();
                                }
                                if (!data.is_offline) {
                                    const lat = parseFloat(data.lat);
                                    const lng = parseFloat(data.lng);
                                    if (!isNaN(lat) && !isNaN(lng) && this.isValidCoord(lat, lng)) {
                                        this.allPoints.push([lat, lng]);
                                    }
                                }
                                this.drawPosition();
                            })
                            .listen('.status.updated', (data) => {
                                this.dispatchStatus = data.status;
                                this.drawPosition();
                            });
                    },
                    
                    destroy() {
                        if (typeof window.Echo !== 'undefined') {
                            window.Echo.leave('dispatch.' + this.dispatchId);
                        }
                        if (this.map) { this.map.remove(); this.map = null; }
                    }
                }));
            };

            if (window.Alpine) {
                registerMap();
            } else {
                document.addEventListener('alpine:init', registerMap);
            }
        })();
    </script>
</div>
