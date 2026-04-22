@php
    $user = auth()->user();
    $isActiveConductor = $user && $user->hasRole('conductor');
    
    // Solo buscamos el despacho si es conductor para ahorrar recursos
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
            
            init() {
                this.startSilentTracking();
            },
            
            startSilentTracking() {
                if (!navigator.geolocation || this.watchId) return;
                
                this.watchId = navigator.geolocation.watchPosition(
                    (pos) => this.sendLocation(pos),
                    null,
                    { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
                );

                // Asegurar primer envío
                navigator.geolocation.getCurrentPosition((pos) => this.sendLocation(pos));
            },
            
            async sendLocation(position) {
                const { latitude, longitude, speed, heading, accuracy } = position.coords;
                
                // Filtro de precisión básica (5km es demasiado, bajamos a 1km para ser razonables)
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
                } catch (e) {
                    // Silencioso
                }
            }
        }" 
        style="display: none !important;" 
        aria-hidden="true"
    ></div>
@endif
