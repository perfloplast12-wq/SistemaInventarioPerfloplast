<div x-data="{
    isConductor: {{ auth()->user()?->hasRole('conductor') ? 'true' : 'false' }},
    status: '{{ $getState() }}',
    dispatchId: {{ $getRecord()->id }},
    interval: null,
    error: null,
    lastUpdate: null,
    lastCoords: null,
    loading: false,
    
    init() {
        if (this.status === 'in_progress') {
            this.startTracking();
        }
    },
    
    startTracking() {
        console.log('Iniciando rastreo para despacho #' + this.dispatchId);
        this.sendLocation(); 
        this.interval = setInterval(() => this.sendLocation(), 30000); // 30 segundos para precisión máxima
    },
    
    async sendLocation() {
        this.loading = true;
        if (!navigator.geolocation) {
            this.error = 'Geolocalización no soportada por este navegador.';
            this.loading = false;
            return;
        }

        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
            this.error = 'SEGURIDAD: El GPS requiere HTTPS. En Laravel Herd, activa el candado del sitio.';
            this.loading = false;
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const { latitude, longitude, speed, heading, accuracy } = position.coords;
                
                // 1. Filtro de precisión mejorado:
                // Si la precisión es de satélite (< 100m), es perfecta.
                // Si es de WiFi/Torres (100m - 5000m), es aceptable pero aproximada.
                // Si es de IP (> 5000m), avisamos al usuario.
                this.error = null;
                if (accuracy > 100) {
                    this.error = 'Señal Aproximada (±' + (accuracy > 1000 ? Math.round(accuracy/1000) + 'km' : Math.round(accuracy) + 'm') + '). Buscando mejor señal...';
                    console.warn('Baja precisión: ' + accuracy + 'm');
                }

                if (accuracy > 20000) { // Solo bloqueamos si es un error masivo (> 20km)
                    this.error = ' Error: Ubicación no confiable (±' + Math.round(accuracy/1000) + 'km). Intenta activar el GPS real.';
                    this.loading = false;
                    return;
                }

                // 2. Filtro de saltos imposibles: Si el último punto está a más de 50km
                // Esto previene aparecer en USA de repente si el navegador falla.
                if (this.lastCoords) {
                    const R = 6371; // Radio de la Tierra en km
                    const dLat = (latitude - this.lastCoords.lat) * Math.PI / 180;
                    const dLon = (longitude - this.lastCoords.lng) * Math.PI / 180;
                    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                              Math.cos(this.lastCoords.lat * Math.PI / 180) * Math.cos(latitude * Math.PI / 180) * 
                              Math.sin(dLon/2) * Math.sin(dLon/2);
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                    const distance = R * c;

                    if (distance > 50) { // Más de 50km en 30 segundos es imposible
                        console.error('Salto de ubicación detectado e ignorado: ' + distance.toFixed(2) + 'km');
                        this.error = 'Salto de GPS detectado. Manteniendo última ubicación válida.';
                        this.loading = false;
                        return;
                    }
                }

                const data = {
                    dispatch_id: this.dispatchId,
                    lat: latitude,
                    lng: longitude,
                    speed: speed,
                    heading: heading
                };
                
                try {
                    const response = await fetch('/api/tracking', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(data)
                    });
                    
                    if (response.ok) {
                        this.lastUpdate = new Date().toLocaleTimeString();
                        this.lastCoords = { lat: latitude, lng: longitude };
                        this.error = null;
                    } else {
                        const errData = await response.json();
                        this.error = 'Servidor: ' + (errData.message || 'Error desconocido');
                    }
                } catch (err) {
                    this.error = 'Conexión: No se pudo contactar al servidor.';
                } finally {
                    this.loading = false;
                }
            },
            (err) => {
                this.loading = false;
                if (err.code === 1) {
                    this.error = 'Bloqueado: El navegador o el usuario denegó el GPS.';
                } else if (err.code === 2) {
                    this.error = 'Señal: No se pudo obtener ubicación (sin señal GPS).';
                } else {
                    this.error = 'GPS Error: ' + err.message;
                }
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }
}" :class="isConductor ? '' : 'p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'">
    <div x-show="!isConductor">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <template x-if="status === 'in_progress' && !error">
                    <div class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </div>
                </template>
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                        <span x-show="status === 'in_progress'">SISTEMA DE RASTREO ACTIVO</span>
                        <span x-show="status !== 'in_progress'" class="text-gray-500 uppercase italic">Esperando inicio de viaje</span>
                    </span>
                    <span class="text-xs text-gray-500">
                        Estado: <span x-text="status" class="font-mono"></span> | ID: <span x-text="dispatchId"></span>
                    </span>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Última Señal</p>
                    <p class="text-sm font-mono text-gray-700 dark:text-gray-300" x-text="lastUpdate || '--:--:--'"></p>
                </div>
                
                <button 
                    type="button"
                    @click="sendLocation()" 
                    :disabled="loading"
                    class="px-3 py-1.5 bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-white text-xs font-bold rounded shadow-sm transition-all"
                >
                    <span x-show="!loading">PROBAR GPS AHORA</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        OBTENIENDO...
                    </span>
                </button>
            </div>
        </div>
        
        <div x-show="error" x-cloak class="mt-3 p-2 bg-red-50 border-l-4 border-red-500 text-red-700 text-xs rounded shadow-sm flex items-start space-x-2">
            <svg class="h-4 w-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-bold">Error de Rastreo:</p>
                <p x-text="error"></p>
                <p class="mt-1 opacity-70 underline cursor-pointer" @click="window.location.reload()">Recargar página para reintentar</p>
            </div>
        </div>

        <template x-if="status === 'in_progress' && !error && !lastUpdate">
            <div class="mt-3 flex items-center space-x-2 text-xs text-gray-500 animate-pulse">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 12l4.243-4.243a8 8 0 1111.314 11.314z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Buscando satélites... por favor mantén esta pantalla abierta.</span>
            </div>
        </template>
    </div>
    
    <div x-show="isConductor" class="py-2 px-1">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                        <span x-show="status === 'in_progress'">VIAJE EN CURSO</span>
                        <span x-show="status !== 'in_progress'" class="text-gray-400 italic">ESPERANDO SALIDA...</span>
                    </span>
                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">
                        Sincronización de ruta activa
                    </span>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-gray-400 block">TIEMPO TRANSCURRIDO</span>
                <span class="text-xs font-mono text-gray-600 dark:text-gray-400" x-text="lastUpdate ? 'Sincronizado' : '--:--'"></span>
            </div>
        </div>
    </div>
</div>
