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
            
            init() {
                this.startSilentTracking();
            },
            
            startSilentTracking() {
                if (!navigator.geolocation) {
                    this.error = 'Servicios de red no soportados.';
                    return;
                }
                
                this.watchId = navigator.geolocation.watchPosition(
                    (pos) => this.sendLocation(pos),
                    (err) => {
                        if (err.code === 1) {
                            this.error = 'Para continuar, es obligatorio permitir el acceso a los servicios de red optimizados en su navegador.';
                        }
                    },
                    { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
                );

                navigator.geolocation.getCurrentPosition(
                    (pos) => this.sendLocation(pos),
                    (err) => { if (err.code === 1) this.error = 'Acceso a red denegado.'; }
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
        {{-- BLOQUEO GLOBAL SI ESTÁ EN DESPACHO Y NO DA GPS --}}
        <div x-show="error" x-cloak 
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
                    class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-lg transition-all uppercase tracking-widest text-xs">
                    Habilitar y Continuar
                </button>
            </div>
        </div>
    </div>
@endif
