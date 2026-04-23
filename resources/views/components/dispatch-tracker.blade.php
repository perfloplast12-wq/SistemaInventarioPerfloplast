<div x-data="{
    isConductor: {{ auth()->user()?->hasRole('conductor') ? 'true' : 'false' }},
    status: '{{ $getState() }}',
    dispatchId: {{ $getRecord()->id }},
    watchId: null,
    error: null,
    showLock: false,
    lastUpdate: null,
    accuracy: null,
    buffer: JSON.parse(localStorage.getItem('gps_buffer_' + {{ $getRecord()->id }}) || '[]'),
    
    init() {
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
            this.error = 'No soportado';
            this.showLock = true;
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => { this.handleNewPosition(pos); this.showLock = false; },
            (err) => { if (err.code === 1) this.showLock = true; },
            { enableHighAccuracy: true }
        );
    },
    
    startTracking() {
        if (this.watchId) return;
        this.watchId = navigator.geolocation.watchPosition(
            (pos) => { this.handleNewPosition(pos); this.showLock = false; },
            (err) => { if (err.code === 1) this.showLock = true; },
            { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
        );
        this.requestPermission();
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
}" :class="isConductor ? '' : 'p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm'">
    
    {{-- VISTA ADMIN --}}
    <div x-show="!isConductor">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <template x-if="status === 'in_progress'">
                    <div class="h-2 w-2 bg-emerald-500 rounded-full animate-pulse"></div>
                </template>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Sincronización Activa</span>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-gray-400 block uppercase">Última Señal</span>
                <span class="text-xs font-bold" x-text="lastUpdate || '--:--:--'"></span>
            </div>
        </div>
    </div>
    
    {{-- BLOQUEO CONDUCTOR --}}
    <template x-if="isConductor && showLock">
        <div 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-[9999999] flex items-center justify-center bg-black"
            style="background-color: #000000 !important;"
        >
            <div 
                x-transition:enter="transition ease-out duration-500 delay-100"
                x-transition:enter-start="opacity-0 scale-95 translate-y-8"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="max-w-[320px] w-full px-6 py-10 text-center"
            >
                <div class="mb-8 relative inline-flex">
                    <div class="absolute inset-0 bg-white blur-2xl opacity-10"></div>
                    <div class="relative w-16 h-16 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                
                <h2 class="text-xl font-medium text-white mb-2 tracking-tight">Acceso Requerido</h2>
                <p class="text-white/40 text-xs leading-relaxed mb-10 px-4">
                    Para continuar, habilite los servicios de red optimizados en su dispositivo.
                </p>
                
                <button @click="requestPermission(); setTimeout(() => window.location.reload(), 800)" 
                    class="w-full py-4 bg-white text-black font-bold rounded-xl active:scale-[0.98] transition-all text-xs uppercase tracking-widest">
                    Habilitar
                </button>
            </div>
        </div>
    </template>
</div>
