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
     style="display: none; position: fixed; inset: 0; z-index: 2147483647; flex-direction: column; align-items: center; justify-content: center; background-color: #000; color: white; padding: 1.5rem; text-align: center; font-family: 'Outfit', sans-serif;">
    
    <div style="background: rgba(255,255,255,0.05); padding: 2.5rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 2rem; backdrop-filter: blur(20px); max-width: 450px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
        <div style="margin-bottom: 2rem; position: relative;">
            <div style="position: absolute; inset: 0; background: #ef4444; filter: blur(20px); opacity: 0.2; border-radius: 50%;"></div>
            <svg style="width: 80px; height: 80px; margin: 0 auto; color: #ef4444; position: relative;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        
        <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 1.25rem; letter-spacing: -0.025em;">Ubicación Obligatoria</h2>
        
        <p style="color: #9ca3af; margin-bottom: 2.5rem; line-height: 1.6; font-size: 1.1rem;">
            Por seguridad, esta aplicación requiere acceso a tu <strong>GPS en tiempo real</strong> para funcionar. 
        </p>

        <div style="background: rgba(239, 68, 68, 0.1); border-radius: 1rem; padding: 1rem; margin-bottom: 2.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <p style="color: #fca5a5; font-size: 0.9rem; margin: 0;">
                Si el GPS está encendido y sigues viendo esto, asegúrate de haber dado permisos al navegador.
            </p>
        </div>
        
        <button type="button" 
                onclick="window.retryGpsConnection(event)" 
                style="width: 100%; background: #ef4444; color: white; font-weight: 700; padding: 1.25rem; border-radius: 1rem; border: none; cursor: pointer; font-size: 1.1rem; box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.4); transition: transform 0.2s;">
            Activar Ubicación
        </button>
    </div>
</div>

<script src="/js/silent-tracker.js?v=1.2"></script>
