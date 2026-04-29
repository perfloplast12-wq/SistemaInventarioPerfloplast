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
                            let attempts = 0;
                            while (typeof window.L === 'undefined' && attempts < 20) {
                                await new Promise(r => setTimeout(r, 100));
                                attempts++;
                            }
                            await this.render(config.locations);
                            this.setupEcho();
                        } catch (e) {
                            console.error('Map Init Fail:', e);
                        } finally {
                            this.isLoaded = true;
                            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 500);
                        }
                    },
                    
                    async loadAssets() {
                        if (window.L) return;
                        const loadStyle = (url, id) => {
                            if (document.getElementById(id)) return Promise.resolve();
                            return new Promise(resolve => {
                                const link = document.createElement('link');
                                link.id = id; link.rel = 'stylesheet'; link.href = url;
                                link.onload = () => resolve(); link.onerror = () => resolve();
                                document.head.appendChild(link);
                            });
                        };
                        const loadScript = (url, id) => {
                            if (document.getElementById(id)) return Promise.resolve();
                            return new Promise(resolve => {
                                const script = document.createElement('script');
                                script.id = id; script.src = url;
                                script.onload = () => resolve(); script.onerror = () => resolve();
                                document.head.appendChild(script);
                            });
                        };
                        await loadStyle('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', 'leaflet-css');
                        await loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'leaflet-js');
                    },

                    isValidCoord(lat, lng) {
                        return lat > 13.0 && lat < 19.0 && lng > -93.0 && lng < -87.0;
                    },

                    getStatusLabel(status) {
                        const labels = { 'pending': 'Pendiente', 'in_progress': 'En Ruta', 'completed': 'Completado', 'delivered': 'Entregado' };
                        return labels[status] || status;
                    },

                    getStatusColor(status) {
                        const colors = { 'pending': '#9ca3af', 'in_progress': '#10b981', 'completed': '#3b82f6', 'delivered': '#8b5cf6' };
                        return colors[status] || '#6b7280';
                    },

                    createTruckIcon() {
                        const baseColor = this.getStatusColor(this.dispatchStatus);
                        const color = this.isOnline ? baseColor : '#6b7280';
                        const pulse = (this.dispatchStatus === 'in_progress' && this.isOnline) ? '<div style="position: absolute; width: 44px; height: 44px; border-radius: 50%; background: rgba(16, 185, 129, 0.3); animation: truck-pulse 2s ease-out infinite;"></div>' : '';
                        const dotColor = (this.dispatchStatus === 'in_progress' && this.isOnline) ? '#22c55e' : '#9ca3af';
                        
                        return L.divIcon({
                            className: 'custom-div-icon',
                            html: `
                                <div style="display: flex; flex-direction: column; align-items: center; transform: translateY(-50%); opacity: ${this.isOnline ? '1' : '0.7'}; transition: all 0.3s ease;">
                                    <div style="position: relative;">
                                        ${pulse}
                                        <div style="width: 44px; height: 44px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.4); border: 3px solid white; background: linear-gradient(135deg, ${color}, ${color}dd);">
                                            <svg style="width: 22px; height: 22px; color: white; transform: rotate(45deg);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1" stroke-width="2"/>
                                            </svg>
                                        </div>
                                        <div style="position: absolute; top: -2px; right: -2px; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; z-index: 10; background: ${dotColor};"></div>
                                    </div>
                                    <div style="margin-top: 6px; padding: 3px 8px; background: rgba(0,0,0,0.75); color: white; font-size: 10px; font-weight: 700; border-radius: 10px; white-space: nowrap; letter-spacing: 0.3px; box-shadow: 0 1px 4px rgba(0,0,0,0.3);">${this.truckName} ${this.isOnline ? '' : '(Fuera de línea)'}</div>
                                </div>
                            `,
                            iconSize: [54, 72], iconAnchor: [27, 54], popupAnchor: [0, -54]
                        });
                    },

                    createPopupContent(lat, lng) {
                        const statusColor = this.getStatusColor(this.dispatchStatus);
                        const isActive = this.dispatchStatus === 'in_progress';
                        const badgeBg = this.isOnline ? (isActive ? '#dcfce7' : '#f3f4f6') : '#fee2e2';
                        const badgeText = this.isOnline ? (isActive ? '#15803d' : '#6b7280') : '#991b1b';
                        const connectionLabel = this.isOnline ? this.getStatusLabel(this.dispatchStatus) : 'Desconectado / GPS Off';
                        
                        return `
                            <div style="min-width: 250px; padding: 14px; font-family: -apple-system, sans-serif;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, ${this.isOnline ? statusColor : '#6b7280'}, ${this.isOnline ? statusColor : '#4b5563'}bb); display: flex; align-items: center; justify-content: center;">
                                        <svg style="width: 22px; height: 22px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1" stroke-width="2"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p style="font-weight: 700; font-size: 15px; color: #111827; margin: 0;">${this.dispatchNumber}</p>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; background: ${badgeBg}; color: ${badgeText};">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: ${this.isOnline ? statusColor : '#ef4444'};"></span>
                                            ${connectionLabel}
                                        </span>
                                    </div>
                                </div>
                                <div style="border-top: 1px solid #e5e7eb; padding-top: 10px; display: grid; gap: 6px;">
                                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280;">👤 <span><strong>Piloto:</strong> ${this.driverName}</span></div>
                                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280;">🚛 <span><strong>Camión:</strong> ${this.truckName}</span></div>
                                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280;">⏲️ <span><strong>Última señal:</strong> ${this.lastSignal || 'Hace un momento'}</span></div>
                                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280;">📍 <span><strong>Posición:</strong> ${lat.toFixed(5)}, ${lng.toFixed(5)}</span></div>
                                </div>
                            </div>
                        `;
                    },

                    async render(encodedLocations) {
                        const el = this.$refs.mapContainer;
                        if (!el || typeof window.L === 'undefined') return;
                        if (this.map) { this.map.remove(); this.map = null; }
                        this.map = L.map(el, { zoomControl: false }).setView([15.47, -90.37], 7);
                        L.control.zoom({ position: 'bottomright' }).addTo(this.map);
                        L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', { attribution: '&copy; Google Maps', maxZoom: 20 }).addTo(this.map);

                        let raw = [];
                        try { raw = JSON.parse(atob(encodedLocations)); } catch(e) { console.error('Data Parse Error:', e); }
                        this.allPoints = raw.map(l => [parseFloat(l.lat), parseFloat(l.lng)]).filter(p => !isNaN(p[0]) && !isNaN(p[1]) && this.isValidCoord(p[0], p[1]));
                        if (raw.length > 0 && raw[raw.length - 1].accuracy == -1) this.isOnline = false;
                        if (this.allPoints.length > 0) this.drawPosition(true);
                    },

                    drawPosition(forceFocus = false) {
                        if (!this.map || this.allPoints.length === 0) return;
                        const lastPoint = this.allPoints[this.allPoints.length - 1];
                        if (this.truckMarker) this.map.removeLayer(this.truckMarker);
                        this.truckMarker = L.marker(lastPoint, { icon: this.createTruckIcon() }).addTo(this.map).bindPopup(this.createPopupContent(lastPoint[0], lastPoint[1]), { maxWidth: 320, closeOnClick: false, autoClose: false });
                        if (forceFocus) this.map.setView(lastPoint, 17); else this.map.panTo(lastPoint);
                    },

                    setupEcho() {
                        if (typeof window.Echo === 'undefined') return;
                        window.Echo.channel('dispatch.' + this.dispatchId)
                            .listen('.location.updated', (data) => {
                                this.isOnline = !data.is_offline;
                                this.lastSignal = new Date(data.timestamp).toLocaleTimeString();
                                if (!data.is_offline) {
                                    const lat = parseFloat(data.lat); const lng = parseFloat(data.lng);
                                    if (!isNaN(lat) && !isNaN(lng) && this.isValidCoord(lat, lng)) { this.allPoints.push([lat, lng]); }
                                }
                                this.drawPosition();
                            })
                            .listen('.status.updated', (data) => {
                                this.dispatchStatus = data.status;
                                this.drawPosition();
                            });
                    },
                    
                    destroy() {
                        if (typeof window.Echo !== 'undefined') window.Echo.leave('dispatch.' + this.dispatchId);
                        if (this.map) { this.map.remove(); this.map = null; }
                    }
                }));
            };
            if (window.Alpine) registerMap(); else document.addEventListener('alpine:init', registerMap);
        })();
    </script>
</div>
