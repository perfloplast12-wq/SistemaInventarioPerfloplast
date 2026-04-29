<div x-data="{
    isConductor: {{ auth()->user()?->hasRole('conductor') ? 'true' : 'false' }},
    status: '{{ $getState() }}',
    dispatchId: {{ $getRecord()->id }},
    watchId: null,
    showLock: false,
    lastUpdate: null,
    accuracy: null,
    buffer: JSON.parse(localStorage.getItem('gps_buffer_' + {{ $getRecord()->id }}) || '[]'),
    wakeLock: null,
    lastGpsTime: Date.now(),
    heartbeatTimer: null,
    
    init() {
        if (this.isConductor && (this.status === 'in_progress' || this.status === 'pending')) {
            this.startTracking();
            this.requestWakeLock();

            // Heartbeat: cada 10s verificar si se perdió el GPS
            this.heartbeatTimer = setInterval(() => {
                const secsSinceGps = (Date.now() - this.lastGpsTime) / 1000;
                if (secsSinceGps > 30 && !this.showLock) {
                    // GPS perdido por más de 30 seg — notificar al servidor
                    this.sendOfflineSignal();
                }
            }, 10000);
        }

        // Reiniciar rastreo si la pestaña vuelve a estar visible
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && this.isConductor && (this.status === 'in_progress' || this.status === 'pending')) {
                // Reactivar watchPosition si se perdió
                if (!this.watchId) this.startTracking();
                this.requestWakeLock();
            }
        });

        setInterval(() => {
            if (this.buffer.length > 0) this.syncBuffer();
        }, 15000);

        this.setupEcho();
    },

    async requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                this.wakeLock = await navigator.wakeLock.request('screen');
            } catch (err) {}
        }
    },

    setupEcho() {
        if (typeof window.Echo === 'undefined') return;
        window.Echo.channel('dispatch.' + this.dispatchId)
            .listen('.location.updated', (data) => {
                this.lastUpdate = new Date(data.timestamp).toLocaleTimeString();
                if (data.is_offline) {
                    this.status = 'offline';
                } else {
                    if (this.status === 'offline') this.status = 'in_progress';
                }
            })
            .listen('.status.updated', (data) => {
                this.status = data.status;
            });
    },
    
    requestPermission() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(
            (pos) => { 
                this.handleNewPosition(pos); 
                this.showLock = false; 
            },
            (err) => { if (err.code === 1) this.showLock = true; },
            { enableHighAccuracy: true }
        );
    },
    
    startTracking() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        
        this.watchId = navigator.geolocation.watchPosition(
            (pos) => { 
                this.lastGpsTime = Date.now();
                this.handleNewPosition(pos); 
                this.showLock = false; 
            },
            (err) => { 
                if (err.code === 1) this.showLock = true;
                // GPS timeout o error — enviar señal offline
                this.sendOfflineSignal();
                // Reintentar watchPosition
                this.watchId = null;
                setTimeout(() => this.startTracking(), 5000);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );

        // Intento silencioso para ocultar el bloqueo si ya hay permiso
        navigator.geolocation.getCurrentPosition(
            (pos) => { this.showLock = false; },
            (err) => { if (err.code === 1) this.showLock = true; }
        );
    },

    async sendOfflineSignal() {
        try {
            await fetch('/api/tracking', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    dispatch_id: this.dispatchId,
                    lat: 0, lng: 0,
                    status: 'offline'
                })
            });
        } catch(e) {}
    },
    
    async handleNewPosition(position) {
        const { latitude, longitude, speed, heading, accuracy } = position.coords;
        this.accuracy = accuracy;
        this.lastUpdate = new Date().toLocaleTimeString();

        const point = {
            dispatch_id: this.dispatchId,
            lat: latitude,
            lng: longitude,
            speed: speed,
            heading: heading,
            timestamp: new Date().toISOString()
        };

        this.buffer.push(point);
        await this.syncBuffer();
    },

    async syncBuffer() {
        if (this.buffer.length === 0) return;
        const point = this.buffer[0];
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
                this.buffer.shift();
                localStorage.setItem('gps_buffer_' + this.dispatchId, JSON.stringify(this.buffer));
            }
        } catch (e) {}
    }
}" 
:class="isConductor ? '' : 'p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm'">
    
    {{-- VISTA ADMIN --}}
    <div x-show="!isConductor">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <template x-if="status === 'in_progress'">
                    <div class="h-2 w-2 bg-emerald-500 rounded-full animate-pulse"></div>
                </template>
                <template x-if="status === 'offline'">
                    <div class="h-2 w-2 bg-red-500 rounded-full animate-pulse"></div>
                </template>
                <span class="text-xs font-bold uppercase tracking-widest"
                      :class="status === 'offline' ? 'text-red-500' : 'text-gray-500'"
                      x-text="status === 'offline' ? '⚠ Señal GPS Perdida' : 'Sincronización Activa'">
                </span>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-gray-400 block uppercase font-bold">Última Señal</span>
                <span class="text-xs font-black" 
                      :class="status === 'offline' ? 'text-red-500' : 'text-primary-600 dark:text-primary-400'" 
                      x-text="lastUpdate || '--:--:--'"></span>
            </div>
        </div>
    </div>
    
    {{-- BLOQUEO TOTAL SIN RECARGAS --}}
    <template x-if="isConductor && showLock">
        <div 
            class="fixed inset-0 z-[9999999] flex flex-col items-center justify-center bg-black"
            style="background-color: #000000 !important;"
        >
            <div class="max-w-[340px] w-full px-8 py-12 text-center">
                <div class="mb-10 relative inline-flex">
                    <div class="absolute inset-0 bg-indigo-500 blur-3xl opacity-30 animate-pulse"></div>
                    <div class="relative w-20 h-20 bg-indigo-600 rounded-[2rem] flex items-center justify-center shadow-2xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 12l4.243-4.243a8 8 0 1111.314 11.314z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
                
                <h2 class="text-2xl font-bold text-white mb-4 tracking-tight">Sincronización requerida</h2>
                <p class="text-white/60 text-sm leading-relaxed mb-10">
                    Para continuar, es obligatorio habilitar los <span class="text-white font-bold">Servicios de Ubicación (GPS)</span> en su navegador.
                </p>
                
                <button 
                    @click="requestPermission()" 
                    class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-xl active:scale-95 transition-all text-xs uppercase tracking-[0.2em]"
                >
                    Habilitar y Continuar
                </button>
            </div>
        </div>
    </template>
</div>
