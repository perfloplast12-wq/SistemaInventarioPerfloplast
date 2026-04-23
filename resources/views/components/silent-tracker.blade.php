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
                    this.error = 'No soportado';
                    this.showLock = true;
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (pos) => { 
                        this.sendLocation(pos); 
                        this.error = null; 
                        this.showLock = false;
                        window.location.reload(); // Recargar para limpiar el estado
                    },
                    (err) => { 
                        if (err.code === 1) { 
                            this.error = 'Denegado'; 
                            this.showLock = true; 
                        } 
                    },
                    { enableHighAccuracy: true }
                );
            },
            
            startSilentTracking() {
                if (!navigator.geolocation) {
                    this.showLock = true;
                    return;
                }
                
                this.watchId = navigator.geolocation.watchPosition(
                    (pos) => { this.sendLocation(pos); this.error = null; this.showLock = false; },
                    (err) => {
                        if (err.code === 1) {
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
        {{-- BLOQUEO GLOBAL REFINADO --}}
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
                    class="max-w-[340px] w-full px-8 py-12 text-center"
                >
                    <div class="mb-10 relative inline-flex">
                        <div class="absolute inset-0 bg-indigo-500 blur-3xl opacity-30 animate-pulse"></div>
                        <div class="relative w-20 h-20 bg-indigo-600 rounded-[2rem] flex items-center justify-center shadow-2xl shadow-indigo-500/20">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 12l4.243-4.243a8 8 0 1111.314 11.314z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-white mb-4 tracking-tight">Sincronización requerida</h2>
                    <p class="text-white/60 text-sm leading-relaxed mb-10">
                        Para continuar con el despacho, es necesario habilitar los <span class="text-white font-bold">Servicios de Ubicación (GPS)</span> en su navegador.
                    </p>
                    
                    <button @click="requestPermission()" 
                        class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl active:scale-[0.96] transition-all text-sm uppercase tracking-widest shadow-xl shadow-indigo-500/20">
                        Habilitar y Continuar
                    </button>
                    
                    <p x-show="error === 'Denegado'" class="mt-6 text-[11px] text-amber-400 font-medium leading-tight">
                        Acceso bloqueado en el navegador.<br>Por favor, actívelo en los ajustes del sitio.
                    </p>
                </div>
            </div>
        </template>
    </div>
@endif
