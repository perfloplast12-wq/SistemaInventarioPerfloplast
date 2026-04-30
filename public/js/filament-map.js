(function() {
    const registerMap = () => {
        if (typeof Alpine === 'undefined') return;
        // Always re-register to allow multiple instances
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
            lastSignalTime: null,
            heartbeatTimer: null,
            pollTimer: null,
            visibilityObserver: null,
            
            async init() {
                try {
                    this.isLoaded = false;
                    this.isOnline = true;

                    // 1. Load Leaflet CSS+JS
                    await this.loadAssets();

                    // 2. Ocultar el spinner e inicializar inmediatamente
                    this.isLoaded = true;

                    // 3. Render map with initial data
                    await this.render(config.locations);

                    // 4. Force immediate poll
                    this.pollLatestPosition();

                    // 5. Connect Echo
                    this.startEcho();

                    // 6. Polling fallback
                    this.pollTimer = setInterval(() => this.pollLatestPosition(), 10000);

                    // 7. Fix for Filament Modal dimensions
                    if (this.$refs.mapContainer) {
                        this.visibilityObserver = new IntersectionObserver((entries) => {
                            if (entries[0].isIntersecting && this.map) {
                                [10, 100, 300, 500].forEach(ms => {
                                    setTimeout(() => {
                                        this.map.invalidateSize(false);
                                        if (this.allPoints.length > 0) this.drawPosition(true);
                                    }, ms);
                                });
                            }
                        });
                        this.visibilityObserver.observe(this.$refs.mapContainer);
                    }

                } catch (e) {
                    console.error('Map Init Fail:', e);
                }
            },
            
            async loadAssets() {
                if (window.L) return;

                // CSS
                if (!document.getElementById('leaflet-css')) {
                    const css = document.createElement('link');
                    css.id = 'leaflet-css'; css.rel = 'stylesheet';
                    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(css);
                }

                // JS
                if (!document.getElementById('leaflet-js')) {
                    await new Promise((resolve) => {
                        const js = document.createElement('script');
                        js.id = 'leaflet-js';
                        js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        js.onload = resolve;
                        js.onerror = resolve;
                        document.head.appendChild(js);
                    });
                }

                // Wait for L to exist
                let wait = 0;
                while (typeof window.L === 'undefined' && wait < 40) {
                    await new Promise(r => setTimeout(r, 80));
                    wait++;
                }
            },

            isValidCoord(lat, lng) {
                return lat > 13.0 && lat < 19.0 && lng > -93.0 && lng < -87.0;
            },

            getStatusLabel(s) {
                return {'pending':'Pendiente','in_progress':'En Ruta','completed':'Completado','delivered':'Entregado'}[s] || s;
            },
            
            async render(locationsRaw) {
                try {
                    const raw = JSON.parse(atob(locationsRaw));
                    this.allPoints = raw
                        .map(l => [parseFloat(l.lat), parseFloat(l.lng)])
                        .filter(p => !isNaN(p[0]) && !isNaN(p[1]) && this.isValidCoord(p[0], p[1]));
                    
                    // Determine online status from the last signal (fallback only, pollLatestPosition will overwrite)
                    if (raw.length > 0) {
                        const last = raw[raw.length - 1];
                        if (last.created_at) {
                            this.lastSignalTime = new Date(last.created_at).getTime();
                            this.lastSignal = new Date(this.lastSignalTime).toLocaleTimeString();
                            const secsSince = (Date.now() - this.lastSignalTime) / 1000;
                            // Detect if the last signal was an explicit offline flag (speed = -1)
                            if (last.speed === -1 || last.speed === '-1' || last.speed === -1.0) {
                                this.isOnline = false;
                            } else {
                                // Utiliza la calibración de vendedores (hasta 2 minutos = 120s)
                                this.isOnline = secsSince <= 120;
                            }
                        }
                    }

                    const el = this.$refs.mapContainer;
                    if (this.map) {
                        this.map.remove();
                        this.map = null;
                    }
                    
                    const center = this.allPoints.length > 0 ? this.allPoints[this.allPoints.length - 1] : [15.47, -90.37];
                    this.map = L.map(el, { zoomControl: false }).setView(center, 7);
                    
                    L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                        attribution: 'Google Maps',
                        maxZoom: 20
                    }).addTo(this.map);
                    L.control.zoom({ position: 'bottomright' }).addTo(this.map);

                    this.drawPosition(true);
                    // Initial invalidate
                    setTimeout(() => this.map.invalidateSize(), 100);
                } catch (e) {
                    console.error('Render Fail:', e);
                }
            },
            drawPosition(fitBounds = false) {
                if (!this.map || this.allPoints.length === 0) return;

                const currentPos = this.allPoints[this.allPoints.length - 1];
                const lat = currentPos[0];
                const lng = currentPos[1];

                const isOffline = !this.isOnline || this.dispatchStatus === 'completed' || this.dispatchStatus === 'delivered';
                const dotColor = isOffline ? '#ef4444' : '#22c55e';
                const pulseHtml = !isOffline ? `<div class="absolute w-[40px] h-[40px] bg-emerald-500/30 rounded-full animate-[truck-pulse_2s_ease-out_infinite]"></div>` : '';
                const stateText = isOffline ? 'Sin señal' : 'En línea';

                const iconHtml = `
                    <div class="flex flex-col items-center" style="transform: translateY(-50%);">
                        <div class="relative flex items-center justify-center">
                            ${pulseHtml}
                            <div class="relative w-[36px] h-[36px] rounded-full shadow-[0_2px_8px_rgba(0,0,0,0.3)] flex items-center justify-center z-10"
                                 style="background: linear-gradient(135deg, #ef4444, #dc2626); border: 2px solid white;">
                                <span style="font-size: 16px;">🚚</span>
                            </div>
                            <div class="absolute -top-1 -right-1 w-[12px] h-[12px] rounded-full border-2 border-white z-20"
                                 style="background-color: ${dotColor};"></div>
                        </div>
                        <div class="mt-1 px-2 py-[2px] bg-black/80 text-white text-[10px] font-bold rounded-full shadow-sm whitespace-nowrap flex items-center gap-1">
                            ${this.truckName}
                            <span class="text-[8px] uppercase tracking-wider" style="color: ${isOffline ? '#fca5a5' : '#86efac'};">
                                ⚠️ ${stateText}
                            </span>
                        </div>
                    </div>
                `;

                const popupHtml = `
                    <div style="min-width:220px;padding:12px;font-family:-apple-system,sans-serif;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:#ef4444;display:flex;align-items:center;justify-content:center;color:white;">
                                🚚
                            </div>
                            <div>
                                <p style="margin:0;font-weight:700;font-size:14px;color:#111827;">${this.dispatchNumber}</p>
                                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 6px;border-radius:9999px;font-size:10px;font-weight:600;background:${isOffline ? '#fef2f2' : '#dcfce7'};color:${isOffline ? '#dc2626' : '#15803d'};">
                                    <span style="width:6px;height:6px;border-radius:50%;background:${dotColor};"></span>
                                    ${stateText}
                                </span>
                            </div>
                        </div>
                        <div style="border-top:1px solid #e5e7eb;padding-top:8px;display:grid;gap:4px;font-size:12px;color:#6b7280;">
                            <div>👤 <strong>Piloto:</strong> ${this.driverName}</div>
                            <div>🚛 <strong>Camión:</strong> ${this.truckName}</div>
                            <div>🗺️ <strong>Ruta:</strong> ${this.routeName}</div>
                            <div>⏲️ <strong>Última señal:</strong> ${this.lastSignal || 'Desconocido'}</div>
                            <div>📍 <strong>Posición:</strong> ${lat.toFixed(5)}, ${lng.toFixed(5)}</div>
                        </div>
                    </div>`;

                const newIcon = L.divIcon({ className: '', html: iconHtml, iconSize: [40, 60], iconAnchor: [20, 30], popupAnchor: [0, -30] });

                if (this.truckMarker) {
                    this.truckMarker.setLatLng([lat, lng]);
                    this.truckMarker.setIcon(newIcon);
                    this.truckMarker.setPopupContent(popupHtml);
                } else {
                    this.truckMarker = L.marker([lat, lng], {
                        icon: newIcon
                    }).addTo(this.map).bindPopup(popupHtml, { autoClose: false, closeOnClick: false });
                    this.truckMarker.openPopup();
                }
                
                if (fitBounds) {
                    this.map.setView([lat, lng], 15);
                }
            },
            
            async pollLatestPosition() {
                try {
                    const resp = await fetch('/api/dispatch-location/' + this.dispatchId + '/latest', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    if (!resp.ok) return;
                    const data = await resp.json();
                    if (!data) return;

                    const lat = parseFloat(data.lat);
                    const lng = parseFloat(data.lng);
                    
                    // 1. Actualizar siempre la información de señal
                    if (data.last_seen_exact) {
                        this.lastSignal = data.last_seen_exact;
                    }
                    if (data.timestamp) {
                        this.lastSignalTime = new Date(data.timestamp).getTime();
                        if (!this.lastSignal) {
                            this.lastSignal = new Date(this.lastSignalTime).toLocaleTimeString();
                        }
                    }

                    const wasOnline = this.isOnline;
                    
                    // 2. Determinar estado Online/Offline
                    if (data.is_offline !== undefined) {
                        this.isOnline = !data.is_offline;
                    } else {
                        const secsSince = (Date.now() - (this.lastSignalTime || Date.now())) / 1000;
                        this.isOnline = secsSince <= 120;
                    }

                    // 3. Validar coordenadas para actualizar posición
                    const isCoordValid = !isNaN(lat) && !isNaN(lng) && this.isValidCoord(lat, lng);
                    
                    if (isCoordValid) {
                        const last = this.allPoints.length > 0 ? this.allPoints[this.allPoints.length - 1] : null;
                        const posChanged = !last || Math.abs(last[0] - lat) > 0.00001 || Math.abs(last[1] - lng) > 0.00001;
                        
                        if (posChanged) {
                            this.allPoints.push([lat, lng]);
                            this.drawPosition();
                        } else if (wasOnline !== this.isOnline) {
                            this.drawPosition();
                        }
                    } else if (wasOnline !== this.isOnline) {
                        this.drawPosition();
                    }
                } catch (e) {
                    console.error('[Map Poll Error]', e);
                }
            },

            startEcho() {
                const connect = () => {
                    if (typeof window.Echo === 'undefined') return false;
                    this.connectEcho();
                    return true;
                };
                if (!connect()) {
                    let tries = 0;
                    const retry = setInterval(() => {
                        if (connect() || ++tries > 15) clearInterval(retry);
                    }, 1000);
                }
            },

            connectEcho() {
                window.Echo.channel('dispatch.' + this.dispatchId)
                    .listen('.location.updated', (data) => {
                        this.lastSignalTime = Date.now();
                        this.lastSignal = new Date().toLocaleTimeString();
                        
                        if (data.is_offline) {
                            this.isOnline = false;
                        } else {
                            this.isOnline = true;
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
                if (this.pollTimer) clearInterval(this.pollTimer);
                if (this.visibilityObserver) this.visibilityObserver.disconnect();
                if (typeof window.Echo !== 'undefined') window.Echo.leave('dispatch.' + this.dispatchId);
                if (this.map) { this.map.remove(); this.map = null; }
            }
        }));
    };

    if (window.Alpine) registerMap();
    else document.addEventListener('alpine:init', registerMap);
})();
