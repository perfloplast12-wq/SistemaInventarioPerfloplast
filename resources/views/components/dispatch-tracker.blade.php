<div x-data="{
    isConductor: {{ auth()->user()?->hasRole('conductor') ? 'true' : 'false' }},
    status: '{{ $getState() }}',
    dispatchId: {{ $getRecord()->id }},
    watchId: null,
    showLock: false,
    lastUpdate: null,
    accuracy: null,
    buffer: JSON.parse(localStorage.getItem('gps_buffer_' + {{ $getRecord()->id }}) || '[]'),
    
    init() {
        if (this.isConductor && (this.status === 'in_progress' || this.status === 'pending')) {
            this.startTracking();
        }

        setInterval(() => {
            if (this.buffer.length > 0) this.syncBuffer();
        }, 20000);
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
        if (this.watchId) return;
        this.watchId = navigator.geolocation.watchPosition(
            (pos) => { 
                this.handleNewPosition(pos); 
                this.showLock = false; 
            },
            (err) => { 
                if (err.code === 1) this.showLock = true; 
            },
            { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
        );

        // Intentar un hit inicial silencioso
        navigator.geolocation.getCurrentPosition(
            (pos) => { this.handleNewPosition(pos); this.showLock = false; },
            (err) => { if (err.code === 1) this.showLock = true; }
        );
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
                <span class="text-[10px] text-gray-400 block uppercase font-bold">Última Señal</span>
                <span class="text-xs font-black text-primary-600 dark:text-primary-400" x-text="lastUpdate || '--:--:--'"></span>
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
                class="max-w-[340px] w-full px-8 py-12 text-center"
            >
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
                <p class="text-white/60 text-sm leading-relaxed mb-10 px-4">
                    Para continuar, es necesario habilitar los <span class="text-white font-bold">Servicios de Ubicación (GPS)</span> en su navegador.
                </p>
                
                <button @click="requestPermission()" 
                    class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl active:scale-[0.96] transition-all text-sm uppercase tracking-widest shadow-xl">
                    Habilitar y Continuar
                </button>
            </div>
        </div>
    </template>
</div>
