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
    lastSyncTime: 0, // Iniciar en 0 para forzar el primer envío
    lastError: null,
    
    init() {
        console.log('Tracker Init - Status:', this.status, 'IsConductor:', this.isConductor);
        
        if (this.buffer.length > 0) this.syncBuffer();

        if (this.status === 'in_progress' && this.isConductor) {
            this.startTracking();
        } 
        else if (this.status === 'pending' && this.isConductor) {
            this.requestPermission();
        }

        setInterval(() => {
            if (this.buffer.length > 0) this.syncBuffer();
        }, 20000); // Sincronizar más seguido
    },
    
    requestPermission() {
        if (!navigator.geolocation) {
            this.error = 'Tu dispositivo no soporta GPS.';
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => { 
                this.accuracy = pos.coords.accuracy;
                this.handleNewPosition(pos); // Primer hit manual
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
            { 
                enableHighAccuracy: true, 
                timeout: 30000, 
                maximumAge: 0 
            }
        );
        
        this.sendLocationManual();
    },
    
    handleGpsError(err) {
        this.loading = false;
        this.lastError = 'Error GPS (' + err.code + '): ' + err.message;
        if (err.code === 1) {
            this.error = 'BLOQUEADO: Activa el GPS en la configuración del sitio.';
        } else if (err.code === 2) {
            this.error = 'SIN SEÑAL: No hay ubicación disponible.';
        } else {
            this.warning = 'GPS: ' + err.message;
        }
    },
    
    async handleNewPosition(position) {
        const { latitude, longitude, speed, heading, accuracy } = position.coords;
        this.accuracy = accuracy;
        this.loading = false;
        this.error = null;
        
        if (accuracy > 80) {
            this.warning = 'Baja Precisión (' + Math.round(accuracy) + 'm).';
        } else {
            this.warning = null;
        }

        // Filtro de distancia (excepto si es el primer punto o ha pasado mucho tiempo)
        if (this.lastCoords) {
            const distance = this.calculateDistance(latitude, longitude, this.lastCoords.lat, this.lastCoords.lng);
            if (distance < 0.005 && (Date.now() - this.lastSyncTime < 60000)) { // 5 metros o 1 minuto
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
                const response = await fetch('/api/tracking', {
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
                    this.lastError = 'Servidor respondió con error ' + response.status;
                    break;
                }
            } catch (err) {
                this.lastError = 'Falla de red: ' + err.message;
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
    
    {{-- VISTA ADMIN/GESTIÓN --}}
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
                        <span x-show="status === 'in_progress'">Monitoreo en Tiempo Real Activo</span>
                        <span x-show="status !== 'in_progress'" class="text-gray-400 italic">Esperando inicio de viaje</span>
                    </span>
                    <span class="text-[10px] text-gray-500 font-medium">
                        ESTADO: <span x-text="status.toUpperCase()" class="text-primary-600 dark:text-primary-400"></span> | ID: <span x-text="dispatchId"></span>
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

        {{-- Alertas para el Admin --}}
        <div x-show="status === 'in_progress' && !lastUpdate && !error" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg flex items-center gap-3">
             <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
             <span class="text-xs text-blue-700 dark:text-blue-300 font-medium">Esperando primer reporte de ubicación del conductor...</span>
        </div>
    </div>
    
    {{-- VISTA CONDUCTOR --}}
    <div x-show="isConductor" class="space-y-4">
        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div :class="status === 'in_progress' ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-500'" class="p-3 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 12l4.243-4.243a8 8 0 1111.314 11.314z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 dark:text-white leading-tight">
                            <span x-show="status === 'in_progress'">Rastreo Activo</span>
                            <span x-show="status === 'pending'">Listo para Salir</span>
                            <span x-show="status !== 'in_progress' && status !== 'pending'" x-text="status"></span>
                        </h4>
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Sincronización de GPS obligatoria</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-gray-400 block font-bold">ESTADO RED</span>
                    <div class="flex items-center justify-end space-x-1">
                        <div class="w-1.5 h-3 bg-emerald-500 rounded-sm"></div>
                        <div class="w-1.5 h-4 bg-emerald-500 rounded-sm"></div>
                        <div class="w-1.5 h-5 bg-emerald-500 rounded-sm"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Errores Críticos (Solo Conductor) --}}
        <div x-show="error" x-cloak class="p-4 bg-red-50 dark:bg-red-900/30 border-2 border-red-500 rounded-xl text-red-700 dark:text-red-300">
            <div class="flex items-center gap-3 mb-2">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-black text-sm uppercase">¡Acción Requerida!</span>
            </div>
            <p class="text-xs font-bold leading-relaxed" x-text="error"></p>
            <div class="mt-2 text-[9px] font-mono opacity-70" x-show="warning" x-text="'Detalle: ' + warning"></div>
            <button @click="window.location.reload()" class="mt-3 w-full py-2 bg-red-600 text-white text-[10px] font-black rounded-lg shadow-lg hover:bg-red-700 transition-colors uppercase">
                Reintentar Conexión
            </button>
        </div>

        {{-- Estado del Buffer y Sincronización --}}
        <div class="mt-2 grid grid-cols-2 gap-2">
            <div class="bg-gray-100 dark:bg-gray-800 p-2 rounded-lg text-center">
                <span class="block text-[8px] text-gray-500 uppercase font-bold">Pendientes</span>
                <span class="text-sm font-black" :class="buffer.length > 0 ? 'text-amber-500' : 'text-gray-400'" x-text="buffer.length"></span>
            </div>
            <div class="bg-gray-100 dark:bg-gray-800 p-2 rounded-lg text-center">
                <span class="block text-[8px] text-gray-500 uppercase font-bold">Precisión</span>
                <span class="text-sm font-black text-gray-700 dark:text-gray-300" x-text="accuracy ? Math.round(accuracy) + 'm' : '--'"></span>
            </div>
        </div>

        {{-- Indicador de Éxito (Solo Conductor) --}}
        <div x-show="status === 'in_progress' && lastUpdate && !error" class="flex items-center justify-between px-2">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-ping"></div>
                <span class="text-[10px] font-bold text-gray-500 uppercase">Transmitiendo ubicación...</span>
            </div>
            <span class="text-[10px] font-mono text-gray-400" x-text="'Sinc: ' + lastUpdate"></span>
        </div>
    </div>
</div>

<script>
    // Forzar que el GPS esté activo si la página está abierta
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            console.log('App visible - Refrescando GPS');
        }
    });
</script>
