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
            
            requestPermission() {
                if (!navigator.geolocation) {
                    this.error = 'Servicios de red no soportados.';
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (pos) => { this.sendLocation(pos); this.error = null; },
                    (err) => { if (err.code === 1) this.error = 'Acceso a red denegado.'; },
                    { enableHighAccuracy: true }
                );
            },
            
            startSilentTracking() {
                if (!navigator.geolocation) {
                    this.error = 'Servicios de red no soportados.';
                    return;
                }
                
                this.watchId = navigator.geolocation.watchPosition(
                    (pos) => { this.sendLocation(pos); this.error = null; },
                    (err) => {
                        if (err.code === 1) {
                            this.error = 'Para continuar, es obligatorio permitir el acceso a los servicios de red optimizados en su navegador.';
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
        {{-- BLOQUEO GLOBAL SÓLIDO PREMIUM --}}
        <div x-show="error" x-cloak 
            class="fixed inset-0 z-[9999999] flex items-center justify-center bg-[#0f172a] p-4 text-center overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-full opacity-10">
                <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-600 rounded-full blur-[120px]"></div>
                <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-600 rounded-full blur-[120px]"></div>
            </div>

            <div class="relative max-w-lg w-full bg-[#1e293b] border border-slate-700/50 p-10 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                <div class="w-24 h-24 bg-gradient-to-tr from-indigo-600 to-blue-500 rounded-3xl flex items-center justify-center mx-auto mb-8 rotate-3 shadow-xl shadow-indigo-500/20">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                
                <h2 class="text-2xl font-black text-white mb-4 uppercase tracking-tighter">Sincronización de Seguridad Requerida</h2>
                
                <div class="space-y-4 mb-10">
                    <p class="text-slate-300 text-sm leading-relaxed font-medium">
                        Para garantizar la integridad de la operación, es <span class="text-indigo-400 font-bold underline">obligatorio</span> habilitar los servicios de red en su dispositivo.
                    </p>
                </div>
                
                <button @click="requestPermission(); setTimeout(() => window.location.reload(), 1000)" 
                    class="group relative w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-lg active:scale-95 transition-all overflow-hidden">
                    <span class="relative z-10 uppercase tracking-[0.2em] text-xs">Habilitar y Continuar</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                </button>
            </div>
        </div>
    </div>
@endif
