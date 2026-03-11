<div x-data="{
    status: '{{ $getState() }}',
    dispatchId: {{ $getRecord()->id }},
    interval: null,
    error: null,
    lastUpdate: null,
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
            this.error = 'Geolocalización no soportada.';
            this.loading = false;
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const data = {
                    dispatch_id: this.dispatchId,
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    speed: position.coords.speed,
                    heading: position.coords.heading
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
}" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
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
