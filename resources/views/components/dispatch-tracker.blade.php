<div x-data="{
    isConductor: {{ auth()->user()?->hasRole('conductor') ? 'true' : 'false' }},
    status: '{{ $getState() }}',
    dispatchId: {{ $getRecord()->id }},
    watchId: null,
    error: null,
    warning: null,
    lastUpdate: null,
    lastCoords: null,
    loading: false,
    accuracy: null,
    buffer: JSON.parse(localStorage.getItem('gps_buffer_' + {{ $getRecord()->id }}) || '[]'),
    lastSyncTime: 0,
    lastError: null,
    
    init() {
        if (this.buffer.length > 0) this.syncBuffer();

        if (this.status === 'in_progress' && this.isConductor) {
            this.startTracking();
        } 
        else if (this.status === 'pending' && this.isConductor) {
            this.requestPermission();
        }

        setInterval(() => {
            if (this.buffer.length > 0) this.syncBuffer();
        }, 20000);
    },
    
    requestPermission() {
        if (!navigator.geolocation) {
            this.error = 'El sistema requiere soporte de red optimizada para funcionar.';
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => { 
                this.accuracy = pos.coords.accuracy;
                this.handleNewPosition(pos);
            },
            (err) => { this.handleGpsError(err); },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    },
    
    startTracking() {
        if (this.watchId) return;
        this.loading = true;
        
        this.watchId = navigator.geolocation.watchPosition(
            (position) => { this.handleNewPosition(position); },
            (err) => { this.handleGpsError(err); },
            { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
        );
        
        this.sendLocationManual();
    },
    
    handleGpsError(err) {
        this.loading = false;
        if (err.code === 1) {
            this.error = 'Para garantizar la integridad del despacho y la sincronización de inventario en tiempo real, es obligatorio permitir el acceso a los servicios de red optimizados. Por favor, habilite la ubicación en los ajustes de su navegador y refresque la página.';
        } else if (err.code === 2) {
            this.error = 'Sincronización Fallida: No hay señal de red disponible. Busque un área abierta.';
        } else {
            this.warning = 'Sincronización: ' + err.message;
        }
    },
    
    async handleNewPosition(position) {
        const { latitude, longitude, speed, heading, accuracy } = position.coords;
        this.accuracy = accuracy;
        this.loading = false;
        this.error = null;
        
        if (this.lastCoords) {
            const distance = this.calculateDistance(latitude, longitude, this.lastCoords.lat, this.lastCoords.lng);
            if (distance < 0.005 && (Date.now() - this.lastSyncTime < 60000)) {
                return;
            }
        }

        const point = {
            dispatch_id: this.dispatchId,
            lat: latitude,
            lng: longitude,
            speed: speed,
            heading: heading,
            timestamp: new Date().toISOString()
        };

        this.buffer.push(point);
        this.saveBuffer();
        await this.syncBuffer();
    },

    saveBuffer() {
        localStorage.setItem('gps_buffer_' + this.dispatchId, JSON.stringify(this.buffer));
    },

    async syncBuffer() {
        if (this.buffer.length === 0) return;
        const pointsToSync = [...this.buffer];
        
        for (const point of pointsToSync) {
            try {
                const url = window.location.origin + '/api/tracking';
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(point)
                });
                
                if (response.ok) {
                    this.buffer = this.buffer.filter(p => p.timestamp !== point.timestamp);
                    this.saveBuffer();
                    this.lastUpdate = new Date().toLocaleTimeString();
                    this.lastCoords = { lat: point.lat, lng: point.lng };
                    this.lastSyncTime = Date.now();
                    this.lastError = null;
                } else {
                    break;
                }
            } catch (err) {
                break;
            }
        }
    },

    async sendLocationManual() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(
            (pos) => this.handleNewPosition(pos),
            (err) => this.handleGpsError(err),
            { enableHighAccuracy: true }
        );
    },

    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
}" :class="isConductor ? '' : 'p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm'">
    
    {{-- VISTA ADMIN/GESTIÓN (El conductor ya no verá esto en su vista) --}}
    <div x-show="!isConductor">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <template x-if="status === 'in_progress'">
                    <div class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </div>
                </template>
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-tight">
                        <span x-show="status === 'in_progress'">Monitoreo de Red Activo</span>
                        <span x-show="status !== 'in_progress'" class="text-gray-400 italic">Esperando inicio de operación</span>
                    </span>
                    <span class="text-[10px] text-gray-500 font-medium">
                        SINCRO: <span x-text="status.toUpperCase()" class="text-primary-600 dark:text-primary-400"></span> | ID: <span x-text="dispatchId"></span>
                    </span>
                </div>
            </div>
            
            <div class="flex items-center space-x-6">
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Última Señal</p>
                    <p class="text-sm font-black text-gray-700 dark:text-gray-300" x-text="lastUpdate || '--:--:--'"></p>
                </div>
                <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Precisión</p>
                    <p class="text-sm font-black text-gray-700 dark:text-gray-300">
                        <span x-text="accuracy ? Math.round(accuracy) + 'm' : '--'"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- BLOQUEO OBLIGATORIO PARA CONDUCTOR --}}
    <div x-show="isConductor && error" x-cloak 
        class="fixed inset-0 z-[999999] flex items-center justify-center bg-slate-900/95 backdrop-blur-md p-6 text-center">
        <div class="max-w-md w-full bg-slate-800 border border-slate-700 p-8 rounded-3xl shadow-2xl">
            <div class="w-20 h-20 bg-indigo-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="text-xl font-black text-white mb-4 uppercase tracking-tight">Sincronización de Seguridad Requerida</h2>
            <p class="text-slate-400 text-sm leading-relaxed mb-8" x-text="error"></p>
            
            <button @click="window.location.reload()" 
                class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-lg shadow-indigo-500/20 transition-all uppercase tracking-widest text-xs">
                Refrescar y Habilitar Servicios
            </button>
            
            <p class="mt-6 text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                Industria de Plástico Perflo Plast
            </p>
        </div>
    </div>
</div>
