@php
    $user = auth()->user();
    $isVendedor = $user && $user->hasAnyRole(['sales', 'vendedor', 'vendedores', 'ventas', 'Vendedor', 'Ventas', 'Sales']);
    $isActiveConductor = $user && $user->hasRole('conductor');
    
    $activeDispatchId = null;
    if ($isActiveConductor) {
        $activeDispatchId = \App\Models\Dispatch::where('driver_id', $user->id)
            ->where('status', 'in_progress')
            ->orderBy('id', 'desc')
            ->value('id');
    }

    // El rastreador se activa si hay un despacho activo O si es un vendedor
    $shouldTrack = $activeDispatchId || $isVendedor;
@endphp

@if($shouldTrack)
    <div 
        x-data="{
            dispatchId: {{ $activeDispatchId ?? 'null' }},
            watchId: null,
            showLock: false,
            heartbeatTimer: null,
            lastSuccessTime: Date.now(),
            
            init() {
                this.startTracking();
                // Verificación continua cada 10 segundos: si no hay GPS, bloquear rápido
                this.heartbeatTimer = setInterval(() => this.checkGpsStatus(), 10000);
            },
            
            destroy() {
                if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
                if (this.watchId !== null) navigator.geolocation.clearWatch(this.watchId);
            },
            
            startTracking() {
                if (!navigator.geolocation) {
                    this.showLock = true;
                    this.sendOfflineSignal();
                    return;
                }
                
                // Limpiamos watch anterior si existe
                if (this.watchId !== null) {
                    navigator.geolocation.clearWatch(this.watchId);
                }
                
                this.watchId = navigator.geolocation.watchPosition(
                    (pos) => { 
                        this.sendLocation(pos); 
                        this.showLock = false;
                        this.lastSuccessTime = Date.now();
                    },
                    (err) => {
                        // Cualquier error de GPS: bloquear inmediatamente
                        this.showLock = true;
                        this.sendOfflineSignal();
                    },
                    { enableHighAccuracy: true, timeout: 8000, maximumAge: 5000 }
                );
            },
            
            // Verificación periódica: si hace más de 30s sin posición exitosa, bloquear
            checkGpsStatus() {
                const secondsSinceLastSuccess = (Date.now() - this.lastSuccessTime) / 1000;
                if (secondsSinceLastSuccess > 30) {
                    this.showLock = true;
                    this.sendOfflineSignal();
                    // Reintentar el watch por si se restauró el GPS
                    this.startTracking();
                }
            },
            
            requestPermission() {
                navigator.geolocation.getCurrentPosition(
                    (pos) => { 
                        this.sendLocation(pos); 
                        this.showLock = false;
                        this.lastSuccessTime = Date.now();
                        // Reiniciar el watch para que siga enviando
                        this.startTracking();
                    },
                    (err) => { 
                        this.showLock = true;
                        this.sendOfflineSignal();
                    },
                    { enableHighAccuracy: true, timeout: 8000 }
                );
            },
            
            async sendLocation(position) {
                const { latitude, longitude, speed, heading, accuracy } = position.coords;
                try {
                    await fetch('/api/tracking', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            dispatch_id: this.dispatchId,
                            lat: latitude,
                            lng: longitude,
                            speed: speed,
                            heading: heading,
                            accuracy: accuracy,
                            status: 'online'
                        })
                    });
                } catch (e) {}
            },
            
            async sendOfflineSignal() {
                try {
                    await fetch('/api/tracking', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            dispatch_id: this.dispatchId,
                            lat: 0,
                            lng: 0,
                            speed: 0,
                            heading: 0,
                            accuracy: 0,
                            status: 'offline'
                        })
                    });
                } catch (e) {}
            }
        }"
    >
        <template x-if="showLock">
            <div 
                class="fixed inset-0 z-[9999999] flex items-center justify-center bg-black"
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
                    <p class="text-white/60 text-sm leading-relaxed mb-10 px-4">
                        Para continuar, es necesario habilitar los <span class="text-white font-bold">Servicios de Ubicación (GPS)</span> en su navegador.
                    </p>
                    
                    <button @click="requestPermission()" 
                        class="w-full py-5 bg-indigo-600 text-white font-black rounded-2xl active:scale-[0.96] transition-all text-sm uppercase tracking-widest shadow-xl">
                        Habilitar y Continuar
                    </button>
                </div>
            </div>
        </template>
    </div>
@endif
