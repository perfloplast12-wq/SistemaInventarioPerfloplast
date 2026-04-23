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
            showLock: false,
            
            init() {
                this.startTracking();
            },
            
            startTracking() {
                if (!navigator.geolocation) return;
                
                // Solo escuchamos. Si falla por permisos, mostramos el bloqueo.
                this.watchId = navigator.geolocation.watchPosition(
                    (pos) => { 
                        this.sendLocation(pos); 
                        this.showLock = false; 
                    },
                    (err) => {
                        if (err.code === 1) { // PERMISSION_DENIED
                            this.showLock = true;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
                );
            },
            
            requestPermission() {
                // Función llamada por el botón (interacción de usuario)
                navigator.geolocation.getCurrentPosition(
                    (pos) => { 
                        this.sendLocation(pos); 
                        this.showLock = false;
                        // ABSOLUTAMENTE NINGUNA RECARGA AQUÍ
                    },
                    (err) => { 
                        if (err.code === 1) this.showLock = true; 
                    },
                    { enableHighAccuracy: true }
                );
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
