@php
    $user = auth()->user();
    $isActiveConductor = $user && $user->hasRole('conductor');
    
    // Solo buscamos el despacho si es conductor para ahorrar recursos
    $activeDispatchId = null;
    if ($isActiveConductor) {
        $activeDispatchId = \App\Models\Dispatch::where('driver_id', $user->id)
            ->where('status', 'in_progress')
            ->value('id');
    }
@endphp

@if($activeDispatchId)
    <div 
        x-data="{
            dispatchId: {{ $activeDispatchId }},
            lastUpdate: null,
            
            init() {
                // Iniciar rastreo silencioso inmediatamente
                this.sendLocation();
                // Intervalo de 30 segundos
                setInterval(() => this.sendLocation(), 30000);
            },
            
            async sendLocation() {
                if (!navigator.geolocation) return;
                
                navigator.geolocation.getCurrentPosition(
                    async (position) => {
                        const { latitude, longitude, speed, heading, accuracy } = position.coords;
                        
                        // Solo enviar si la precisión es aceptable (< 5km) para evitar basura
                        if (accuracy > 5000) return;

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
                        } catch (e) {
                            // Silencioso
                        }
                    },
                    null, // Error silencioso
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }
        }" 
        style="display: none !important;" 
        aria-hidden="true"
    ></div>
@endif
