@php
    $user = auth()->user();
    $isVendedor = $user && $user->hasAnyRole(['sales', 'vendedor', 'vendedores', 'ventas', 'Vendedor', 'Ventas', 'Sales']);
    $isConductor = $user && $user->hasRole('conductor');
    
    if (!$isVendedor && !$isConductor) return;

    $activeDispatchId = null;
    if ($isConductor) {
        $activeDispatchId = \App\Models\Dispatch::where('driver_id', $user->id)
            ->where('status', 'in_progress')
            ->orderBy('id', 'desc')
            ->value('id');
    }
@endphp

<div id="gps-tracker-config" 
     data-should-track="{{ ($activeDispatchId || $isVendedor) ? 'true' : 'false' }}"
     data-dispatch-id="{{ $activeDispatchId ?? 'null' }}"
     data-csrf-token="{{ csrf_token() }}">
</div>

<script>
    window.trackerConfig = {
        shouldTrack: {{ ($activeDispatchId || $isVendedor) ? 'true' : 'false' }},
        dispatchId: {{ $activeDispatchId ?? 'null' }},
        csrfToken: '{{ csrf_token() }}'
    };
</script>

<div id="gps-lock-overlay" 
     style="display: none; position: fixed; inset: 0; z-index: 999999; flex-direction: column; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.95); color: white; padding: 1.5rem; text-align: center; font-family: sans-serif;">
    
    <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 1.5rem; backdrop-filter: blur(10px); max-width: 400px; width: 100%;">
        <div style="margin-bottom: 1.5rem;">
            <svg style="width: 64px; height: 64px; margin: 0 auto; color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        
        <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem;">Sincronización requerida</h2>
        
        <p style="color: #d1d5db; margin-bottom: 2rem; line-height: 1.5;">
            Para continuar usando la aplicación, es necesario mantener habilitados los <strong>Servicios de Ubicación (GPS)</strong>.
        </p>
        
        <button onclick="window.retryGpsConnection()" 
                style="width: 100%; background: white; color: black; font-weight: 700; padding: 1rem; border-radius: 0.75rem; border: none; cursor: pointer; font-size: 1rem; transition: all 0.2s;">
            Habilitar y Continuar
        </button>
        
        <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 1.5rem;">
            Si el problema persiste, revisa los permisos de tu navegador o reinicia tu GPS.
        </p>
    </div>
</div>

<script src="/js/silent-tracker.js?v=1.1"></script>
