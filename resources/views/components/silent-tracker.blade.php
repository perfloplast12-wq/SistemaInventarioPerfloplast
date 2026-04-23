@php
    $user = auth()->user();
    $isActiveConductor = $user && $user->hasRole('conductor');
    
    $activeDispatchId = null;
    if ($isActiveConductor) {
        $activeDispatchId = \App\Models\Dispatch::where('driver_id', $user->id)
            ->where('status', 'in_progress')
            ->orderBy('id', 'desc')
            ->value('id');
    }
@endphp

@if($activeDispatchId)
    <div 
        x-data="{
            dispatchId: {{ $activeDispatchId }},
            watchId: null,
            error: null,
            showLock: false,
            
            init() {
                this.startSilentTracking();
            },
            
            requestPermission() {
                if (!navigator.geolocation) {
                    this.error = 'Servicios de red no soportados.';
                    this.showLock = true;
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (pos) => { this.sendLocation(pos); this.error = null; this.showLock = false; },
                    (err) => { if (err.code === 1) { this.error = 'Acceso denegado'; this.showLock = true; } },
                    { enableHighAccuracy: true }
                );
            },
            
            startSilentTracking() {
                if (!navigator.geolocation) {
                    this.error = 'Servicios de red no soportados.';
                    this.showLock = true;
                    return;
                }
                
                this.watchId = navigator.geolocation.watchPosition(
                    (pos) => { this.sendLocation(pos); this.error = null; this.showLock = false; },
                    (err) => {
                        if (err.code === 1) {
                            this.error = 'Requerido';
                            this.showLock = true;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
                );

                this.requestPermission();
            },
            
            async sendLocation(position) {
                const { latitude, longitude, speed, heading, accuracy } = position.coords;
                if (accuracy > 1000) return;

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
                            heading: heading
                        })
                    });
                } catch (e) {}
            }
        }"
    >
        {{-- BLOQUEO GLOBAL REDISEÑADO (ESTILO APPLE/STRIKE) --}}
        <template x-if="showLock">
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
                        <div class="absolute inset-0 bg-blue-500 blur-2xl opacity-20 animate-pulse"></div>
                        <div class="relative w-16 h-16 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>
                    
                    <h2 class="text-xl font-medium text-white mb-3 tracking-tight">Sincronización requerida</h2>
                    <p class="text-white/40 text-sm leading-relaxed mb-10 px-4">
                        Para continuar con el despacho, habilite los servicios de red en su dispositivo.
                    </p>
                    
                    <button @click="requestPermission(); setTimeout(() => window.location.reload(), 800)" 
                        class="w-full py-4 bg-white text-black font-semibold rounded-xl active:scale-[0.98] transition-all text-sm tracking-wide">
                        Habilitar y Continuar
                    </button>
                    
                    <p class="mt-8 text-[10px] text-white/20 font-medium uppercase tracking-[0.2em]">
                        Perflo Plast System
                    </p>
                </div>
            </div>
        </template>
    </div>
@endif
