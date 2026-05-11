(function() {
    const registerMap = () => {
        if (typeof Alpine === 'undefined') return;
        
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
            orders: config.orders ? JSON.parse(atob(config.orders)) : [],
            isOnline: true,
            lastSignal: null,
            lastSignalTime: null,
            pollTimer: null,
            visibilityObserver: null,
            
            async init() {
                try {
                    this.isLoaded = false;
                    this.isOnline = true;

                    await this.loadAssets();
                    this.isLoaded = true;

                    // Render map with initial data
                    await this.render(config.locations);

                    // Polling
                    this.pollLatestPosition();
                    this.pollTimer = setInterval(() => this.pollLatestPosition(), 10000);

                    // Real-time
                    this.startEcho();

                    // Visibility fix
                    if (this.$refs.mapContainer) {
                        this.visibilityObserver = new IntersectionObserver((entries) => {
                            if (entries[0].isIntersecting && this.map) {
                                setTimeout(() => {
                                    this.map.invalidateSize();
                                    if (this.allPoints.length > 0) this.drawPosition(true);
                                }, 200);
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
                if (!document.getElementById('leaflet-css')) {
                    const css = document.createElement('link');
                    css.id = 'leaflet-css'; css.rel = 'stylesheet';
                    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(css);
                }
                if (!document.getElementById('leaflet-js')) {
                    await new Promise((resolve) => {
                        const js = document.createElement('script');
                        js.id = 'leaflet-js';
                        js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        js.onload = resolve; js.onerror = resolve;
                        document.head.appendChild(js);
                    });
                }
                let wait = 0;
                while (typeof window.L === 'undefined' && wait < 50) {
                    await new Promise(r => setTimeout(r, 100));
                    wait++;
                }
            },

            isValidCoord(lat, lng) {
                return lat > 13.0 && lat < 19.0 && lng > -93.0 && lng < -87.0;
            },
            
            async render(locationsRaw) {
                try {
                    const raw = JSON.parse(atob(locationsRaw));
                    this.allPoints = raw
                        .map(l => [parseFloat(l.lat), parseFloat(l.lng)])
                        .filter(p => !isNaN(p[0]) && !isNaN(p[1]) && this.isValidCoord(p[0], p[1]));
                    
                    if (raw.length > 0) {
                        const last = raw[raw.length - 1];
                        if (last.created_at) {
                            this.lastSignalTime = new Date(last.created_at).getTime();
                            this.lastSignal = new Date(this.lastSignalTime).toLocaleTimeString();
                            const secsSince = (Date.now() - this.lastSignalTime) / 1000;
                            this.isOnline = (last.speed !== -1 && last.speed !== '-1') && (secsSince <= 120);
                        }
                    }

                    const el = this.$refs.mapContainer;
                    if (this.map) { this.map.remove(); this.map = null; }
                    
                    const center = this.allPoints.length > 0 ? this.allPoints[this.allPoints.length - 1] : [15.47, -90.37];
                    this.map = L.map(el, { zoomControl: false }).setView(center, this.allPoints.length > 0 ? 15 : 7);
                    
                    L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                        attribution: 'Google Maps', maxZoom: 20
                    }).addTo(this.map);
                    L.control.zoom({ position: 'bottomright' }).addTo(this.map);

                    this.drawPosition();
                    this.drawOrders();

                    if (this.orders && this.orders.length > 0) {
                        const bounds = [];
                        if (this.allPoints.length > 0) {
                            bounds.push(this.allPoints[this.allPoints.length - 1]);
                        } else {
                            bounds.push(center);
                        }
                        this.orders.forEach(o => {
                            if (this.isValidCoord(o.lat, o.lng)) {
                                bounds.push([o.lat, o.lng]);
                            }
                        });
                        if (bounds.length > 1) {
                            setTimeout(() => {
                                if (this.map) this.map.fitBounds(bounds, { padding: [50, 50] });
                            }, 500);
                        }
                    }

                    setTimeout(() => {
                        if (this.map) this.map.invalidateSize();
                    }, 300);
                } catch (e) { console.error('Render Fail:', e); }
            },

            drawPosition(fitBounds = false) {
                if (!this.map) return;

                let lat = 15.3725; // Default lat (Cobán/Verapaces general area)
                let lng = -90.3800; // Default lng
                let hasRealLocation = false;

                if (this.allPoints.length > 0) {
                    const currentPos = this.allPoints[this.allPoints.length - 1];
                    lat = currentPos[0];
                    lng = currentPos[1];
                    hasRealLocation = true;
                }

                const isOffline = !this.isOnline || this.dispatchStatus === 'completed' || this.dispatchStatus === 'delivered';
                const dotColor = isOffline ? '#ef4444' : '#22c55e';
                const pulseHtml = (!isOffline && hasRealLocation) ? `<div class="absolute w-[40px] h-[40px] bg-emerald-500/30 rounded-full animate-[truck-pulse_2s_ease-out_infinite]"></div>` : '';
                const stateText = !hasRealLocation ? 'Esperando Señal...' : (isOffline ? 'Sin señal' : 'En línea');

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
                            <div style="width:32px;height:32px;border-radius:50%;background:#ef4444;display:flex;align-items:center;justify-content:center;color:white;">🚚</div>
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
                    this.truckMarker = L.marker([lat, lng], { icon: newIcon }).addTo(this.map).bindPopup(popupHtml, { autoClose: false, closeOnClick: false });
                    this.truckMarker.openPopup();
                }
                
                if (fitBounds) this.map.setView([lat, lng], 15);
            },

            drawOrders() {
                if (!this.map || !this.orders || this.orders.length === 0) return;

                this.orders.forEach(o => {
                    if (!this.isValidCoord(o.lat, o.lng)) return;

                    const isCompleted = o.status === 'completed';
                    const pinColorStart = isCompleted ? '#10b981' : '#3b82f6';
                    const pinColorEnd = isCompleted ? '#047857' : '#1d4ed8';
                    const glowColor = isCompleted ? 'rgba(16, 185, 129, 0.35)' : 'rgba(59, 130, 246, 0.35)';
                    const badgeClass = isCompleted
                        ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                        : 'bg-blue-500/10 text-blue-400 border border-blue-500/30';

                    const statusBadgeHtml = isCompleted
                        ? `<span style="display:inline-flex;align-items:center;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700;background:#dcfce7;color:#15803d;margin-top:6px;">🟢 Entregado</span>`
                        : `<span style="display:inline-flex;align-items:center;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700;background:#dbeafe;color:#1e40af;margin-top:6px;">🔵 Pendiente</span>`;

                    const iconHtml = `
                        <div style="position: relative; width: 44px; height: 64px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end;">
                            <!-- Pulsing glowing background -->
                            <div class="absolute w-[34px] h-[34px] rounded-full animate-ping" 
                                 style="background: ${glowColor}; opacity: 0.75; top: 2px; animation-duration: 3s; z-index: 1;"></div>
                            
                            <!-- Elegant SVG Pin -->
                            <div style="position: relative; width: 34px; height: 44px; z-index: 2; filter: drop-shadow(0px 3px 6px rgba(0,0,0,0.25));">
                                <svg width="34" height="44" viewBox="0 0 34 44" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:100%;">
                                    <!-- Pin body with dynamic gradient id -->
                                    <path d="M17 0C7.61116 0 0 7.61116 0 17C0 27.5 17 44 17 44C17 44 34 27.5 34 17C34 7.61116 26.3888 0 17 0Z" fill="url(#pinGrad-${o.number})"/>
                                    <!-- Inner white circle -->
                                    <circle cx="17" cy="17" r="10" fill="#FFFFFF"/>
                                    
                                    <defs>
                                        <linearGradient id="pinGrad-${o.number}" x1="17" y1="0" x2="17" y2="44" gradientUnits="userSpaceOnUse">
                                            <stop offset="0%" stop-color="${pinColorStart}"/>
                                            <stop offset="100%" stop-color="${pinColorEnd}"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                                <!-- Home Emoji / Icon centered inside white circle -->
                                <div style="position: absolute; top: 9px; left: 9px; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; line-height:1;">
                                    ${isCompleted ? '✅' : '🏠'}
                                </div>
                            </div>
                            
                            <!-- Premium Order Number Label -->
                            <div class="mt-1 px-1.5 py-[2px] ${badgeClass} text-[9px] font-bold rounded shadow-sm whitespace-nowrap text-center" 
                                 style="z-index: 3; font-family: 'Outfit', sans-serif; backdrop-filter: blur(4px);">
                                ${o.number}
                            </div>
                        </div>
                    `;

                    const popupHtml = `
                        <div style="min-width:180px;padding:8px;font-family:-apple-system,sans-serif;">
                            <h4 style="margin:0 0 4px 0;font-weight:bold;color:${isCompleted ? '#10b981' : '#2563eb'};font-size:12px;">Punto de Entrega</h4>
                            <p style="margin:0;font-size:11px;font-weight:600;color:#111827;">${o.customer}</p>
                            <p style="margin:2px 0 0 0;font-size:10px;color:#4b5563;line-height:1.4;">${o.address}</p>
                            ${statusBadgeHtml}
                            <p style="margin:6px 0 0 0;font-size:9px;color:#9ca3af;border-top:1px solid #f3f4f6;padding-top:4px;">Pedido: ${o.number}</p>
                        </div>
                    `;

                    const orderIcon = L.divIcon({
                        className: '',
                        html: iconHtml,
                        iconSize: [44, 64],
                        iconAnchor: [22, 64],
                        popupAnchor: [0, -64]
                    });

                    L.marker([o.lat, o.lng], { icon: orderIcon })
                        .addTo(this.map)
                        .bindPopup(popupHtml);
                });
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
                    
                    if (data.last_seen_exact) this.lastSignal = data.last_seen_exact;
                    if (data.timestamp) {
                        this.lastSignalTime = new Date(data.timestamp).getTime();
                        if (!this.lastSignal) this.lastSignal = new Date(this.lastSignalTime).toLocaleTimeString();
                    }

                    const wasOnline = this.isOnline;
                    if (data.is_offline !== undefined) {
                        this.isOnline = !data.is_offline;
                    } else {
                        const secsSince = (Date.now() - (this.lastSignalTime || Date.now())) / 1000;
                        this.isOnline = secsSince <= 120;
                    }

                    const isCoordValid = !isNaN(lat) && !isNaN(lng) && this.isValidCoord(lat, lng);
                    if (isCoordValid) {
                        const last = this.allPoints.length > 0 ? this.allPoints[this.allPoints.length - 1] : null;
                        if (!last || Math.abs(last[0] - lat) > 0.00001 || Math.abs(last[1] - lng) > 0.00001) {
                            this.allPoints.push([lat, lng]);
                            this.drawPosition();
                        } else if (wasOnline !== this.isOnline) {
                            this.drawPosition();
                        }
                    } else if (wasOnline !== this.isOnline) {
                        this.drawPosition();
                    }
                } catch (e) { console.error('[Map Poll Error]', e); }
            },

            startEcho() {
                const connect = () => {
                    if (typeof window.Echo === 'undefined') return false;
                    this.connectEcho(); return true;
                };
                if (!connect()) {
                    let tries = 0;
                    const retry = setInterval(() => {
                        if (connect() || ++tries > 10) clearInterval(retry);
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
                            const lat = parseFloat(data.lat); const lng = parseFloat(data.lng);
                            if (!isNaN(lat) && !isNaN(lng) && this.isValidCoord(lat, lng)) {
                                this.allPoints.push([lat, lng]);
                            }
                        }
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
