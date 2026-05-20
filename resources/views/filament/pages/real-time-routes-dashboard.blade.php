<x-filament-panels::page>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        .dispatch-map-page {
            width: 100%;
            max-width: none;
            font-family: 'Outfit', sans-serif;
            position: relative;
        }

        .dispatch-map-page .dispatch-card {
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
        }

        .dispatch-map-page .dispatch-map {
            height: min(68dvh, 750px) !important;
            min-height: 560px;
            border-radius: 18px;
            overflow: hidden;
            position: relative;
        }

        .dispatch-map-page .dispatch-side {
            height: min(85dvh, 900px) !important;
            min-height: 600px;
            overflow-y: auto;
            position: sticky;
            top: 1rem;
        }

        /* Custom Scrollbar */
        .dispatch-map-page .scrollbar-thin::-webkit-scrollbar {
            height: 5px;
            width: 5px;
        }
        .dispatch-map-page .scrollbar-thin::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.1);
            border-radius: 99px;
        }
        .dispatch-map-page .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 99px;
        }
        .dispatch-map-page .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #4f46e5;
        }

        /* Zoom Control customization to match screenshot */
        .dispatch-map-page .leaflet-control-zoom {
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
        }
        .dispatch-map-page .leaflet-control-zoom a {
            width: 32px !important;
            height: 32px !important;
            background: #0a1120 !important;
            color: #94a3b8 !important;
            border: none !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
            line-height: 32px !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            transition: all 0.2s ease !important;
        }
        .dispatch-map-page .leaflet-control-zoom a:hover {
            background: #4f46e5 !important;
            color: #ffffff !important;
        }

        @keyframes ping {
            0% { transform: scale(1); opacity: 1; }
            70%, 100% { transform: scale(2); opacity: 0; }
        }
    </style>

    <div 
        x-data="realTimeDashboardComponent()"
        class="dispatch-map-page min-h-screen dark:bg-[#07111f] bg-slate-50 dark:text-white text-slate-900"
    >
        @php
            $stats = $this->getTabsStats();
            $dispatches = $this->getDispatches();
        @endphp

        <div class="grid xl:grid-cols-[minmax(0,1fr)_340px] gap-4 items-start w-full">
            <!-- COLUMNA IZQUIERDA -->
            <div class="flex flex-col gap-4 min-w-0">
                <!-- CABECERA PRINCIPAL -->
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4 min-w-0">
                        <span class="w-11 h-11 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0 shadow-lg shadow-indigo-600/5">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-xl font-extrabold tracking-tight dark:text-white text-slate-900 leading-tight">
                                Mapa de Rutas en Tiempo Real
                            </h2>
                            <p class="text-xs dark:text-slate-400 text-slate-500 font-medium mt-0.5">Visualiza la ubicación de los pilotos y el estado de sus entregas</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Botón Tabla -->
                        <a href="{{ \App\Filament\Resources\DispatchResource::getUrl('index') }}"
                           class="px-4 py-2 rounded-xl dark:bg-[#0b1728] bg-white border dark:border-slate-700 border-slate-200 dark:text-slate-300 text-slate-700 dark:hover:text-white hover:text-slate-900 font-bold text-xs transition-all duration-300 flex items-center gap-2 active:scale-[0.98]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Tabla
                        </a>

                        <!-- Botón Nuevo Despacho -->
                        <a href="{{ \App\Filament\Resources\DispatchResource::getUrl('create') }}" 
                           class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs transition-all duration-300 flex items-center gap-2 shadow-lg shadow-violet-600/20 rounded-xl active:scale-[0.98]">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Nuevo Despacho
                        </a>
                    </div>
                </div>
                <!-- CONTENEDOR DE CONTROLES DE PESTAÑAS Y CAPAS DIRECTAMENTE SOBRE EL MAPA -->
                <div class="flex items-center justify-between mb-3 w-full flex-wrap gap-3">
                    <!-- Filtros Tabificados (Izquierda) -->
                    <div class="flex flex-wrap items-center gap-1 dark:bg-[#0b1728] bg-white p-1 rounded-2xl border dark:border-slate-800 border-slate-200">
                        @php
                            $tabs = [
                                'todos' => ['label' => 'Todos', 'badge_class' => 'bg-[#6366f1]'],
                                'in_progress' => ['label' => 'En Proceso', 'badge_class' => 'bg-[#0ea5e9]'],
                                'completed' => ['label' => 'Completados', 'badge_class' => 'bg-[#22c55e]'],
                                'pending' => ['label' => 'Pendientes', 'badge_class' => 'bg-[#f59e0b]'],
                                'delivered' => ['label' => 'Con Devolución', 'badge_class' => 'bg-[#f97316]'],
                            ];
                        @endphp
                        @foreach($tabs as $key => $t)
                            <button 
                                wire:click="setTab('{{ $key }}')"
                                class="px-3.5 py-1.5 font-extrabold text-[11px] rounded-xl flex items-center gap-2 transition-all duration-300 {{ $activeTab === $key ? 'bg-violet-600 text-white shadow-md' : 'bg-transparent dark:text-slate-300 text-slate-700 border border-transparent dark:hover:text-white hover:text-slate-900 dark:hover:bg-slate-800/40 hover:bg-slate-100' }}"
                            >
                                {{ $t['label'] }}
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black text-white shrink-0 {{ $t['badge_class'] }}">
                                    {{ $stats[$key] ?? 0 }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <!-- Controles de Capa y Maximizado (Derecha) -->
                    <div class="flex items-center gap-2.5">
                        <!-- Selector de Capas -->
                        <div class="flex items-center bg-[#0a1120]/60 p-1 rounded-2xl border border-slate-800/80 backdrop-blur-sm">
                            <span class="text-[10px] text-slate-500 font-extrabold px-2.5">Vista</span>
                            <div class="flex gap-1">
                                <button 
                                    type="button"
                                    @click="setMapLayer('map')"
                                    :class="mapLayer === 'map' ? 'bg-[#4f46e5] text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800/40'"
                                    class="px-3.5 py-1.5 font-extrabold text-[10px] rounded-xl transition-all duration-300"
                                >
                                    Mapa
                                </button>
                                <button 
                                    type="button"
                                    @click="setMapLayer('satellite')"
                                    :class="mapLayer === 'satellite' ? 'bg-[#4f46e5] text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800/40'"
                                    class="px-3.5 py-1.5 font-extrabold text-[10px] rounded-xl transition-all duration-300"
                                >
                                    Satélite
                                </button>
                            </div>
                        </div>

                        <!-- Maximizar -->
                        <button 
                            type="button"
                            @click="toggleFullscreen()"
                            class="w-9 h-9 flex items-center justify-center bg-[#0a1120]/60 hover:bg-slate-800 border border-slate-800/80 text-slate-400 hover:text-white rounded-2xl transition-all duration-300"
                            title="Maximizar Mapa"
                        >
                            <svg class="w-5 h-5 text-slate-300 hover:text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4M16 4h4v4M4 16v4h4M16 20h4v-4" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- CONTENEDOR MAPA -->
                <div id="dispatch-map-card" class="dispatch-map relative w-full shadow-2xl">
                    <div id="dispatch-dashboard-map" class="absolute inset-0 z-0" wire:ignore></div>

                    <!-- Overlay de Piloto Activo en la esquina inferior izquierda (flotante premium) -->
                    <template x-if="selectedPilot">
                        <div class="absolute bottom-4 left-4 z-[999] bg-[#070d19]/90 backdrop-blur-md px-4 py-3.5 rounded-2xl border border-slate-800/80 shadow-2xl flex items-center gap-3.5 min-w-[270px]">
                            <!-- Initials Avatar -->
                            <div class="w-10 h-10 rounded-full bg-[#13223f]/80 border border-slate-700/50 flex items-center justify-center font-extrabold text-xs text-white" x-text="selectedPilot.driver_initials"></div>
                            
                            <div class="flex flex-col text-left">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-white" x-text="selectedPilot.driver_name + ' (' + selectedPilot.truck_name + ')'"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="text-[10px] text-emerald-400 font-bold" x-text="selectedPilot.status === 'in_progress' ? 'En ruta' : 'Completado'"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1" x-text="'Velocidad: ' + (getPilotLocationDetails() ? getPilotLocationDetails().speed : '45') + ' km/h'"></p>
                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="'Última actualización: ' + (getPilotLocationDetails() ? getPilotLocationDetails().updated_at : 'hace 1 min')"></p>
                            </div>
                        </div>
                    </template>
                                <!-- RESUMEN HORIZONTAL DE LA RUTA (Abajo del mapa) -->
                <template x-if="selectedPilotStops.length > 0">
                    <div class="dispatch-card p-5 mt-4 flex flex-col gap-4">
                        <h4 class="text-xs font-black text-slate-400 tracking-wider uppercase flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Resumen de la ruta de <span class="text-indigo-400 font-bold" x-text="selectedPilot.driver_name"></span>
                        </h4>
                        
                        <!-- Timeline horizontal de paradas -->
                        <div class="relative flex items-center justify-between w-full mt-2 px-6 overflow-x-auto pb-4 gap-4 scrollbar-thin">
                            <template x-for="(stop, idx) in selectedPilotStops" :key="stop.id">
                                <div class="flex items-center grow last:grow-0">
                                    <!-- Nodo de la parada -->
                                    <div 
                                        class="flex flex-col items-center z-10 min-w-[125px] text-center group cursor-pointer" 
                                        @click="zoomToStop(stop)"
                                    >
                                        <!-- Círculo del Punto (Sólido Premium) -->
                                        <div 
                                            class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs transition-all duration-300 shadow-md"
                                            :class="stop.status === 'completed' 
                                                ? 'bg-[#10b981] text-white shadow-emerald-950/20' 
                                                : (stop.status === 'returned' 
                                                    ? 'bg-[#f97316] text-white shadow-orange-950/20' 
                                                    : (stop.number === selectedPilotStops.length 
                                                        ? 'bg-[#ef4444] text-white shadow-rose-950/20' 
                                                        : 'bg-[#6366f1] text-white shadow-indigo-950/20'))"
                                        >
                                            <span x-text="stop.status === 'completed' ? '✓' : (stop.number === selectedPilotStops.length ? 'P' : stop.number)"></span>
                                        </div>

                                        <!-- Textos del nodo alineados de acuerdo al mockup -->
                                        <div class="mt-2.5 flex flex-col items-center text-center">
                                            <template x-if="stop.number === selectedPilotStops.length">
                                                <div class="flex flex-col items-center">
                                                    <span class="text-[11px] font-extrabold text-[#ef4444] uppercase tracking-wider">Destino</span>
                                                    <span class="text-[10px] text-slate-300 max-w-[115px] leading-tight mt-0.5" x-text="getShortAddress(stop.delivery_address)"></span>
                                                    <span class="text-[10px] font-bold text-[#f59e0b] mt-0.5">Pendiente</span>
                                                    <span class="text-[10px] text-slate-500 font-mono font-medium mt-0.5" x-text="formatTime(stop, idx)"></span>
                                                </div>
                                            </template>
                                            <template x-if="stop.number !== selectedPilotStops.length">
                                                <div class="flex flex-col items-center">
                                                    <span class="text-[11px] font-bold text-white max-w-[115px] leading-tight" x-text="getShortAddress(stop.delivery_address)"></span>
                                                    <span 
                                                        class="text-[10px] font-bold mt-1" 
                                                        :class="stop.status === 'completed' ? 'text-[#10b981]' : (stop.status === 'returned' ? 'text-[#f97316]' : 'text-[#f59e0b]')"
                                                        x-text="stop.status === 'completed' ? 'Completado' : (stop.status === 'returned' ? 'Devuelto' : 'Pendiente')"
                                                    ></span>
                                                    <span class="text-[10px] text-slate-500 font-mono font-medium mt-0.5" x-text="formatTime(stop, idx)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Línea conectora horizontal punteada condicional -->
                                    <template x-if="idx < selectedPilotStops.length - 1">
                                        <div class="h-[2px] grow min-w-[40px] mx-2 shrink-0 border-t-2 border-dashed transition-all duration-300"
                                             :class="(stop.status === 'completed' && selectedPilotStops[idx+1].status === 'completed') ? 'border-[#10b981]' : 'border-slate-800'"></div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- COLUMNA PANEL LATERAL (Detalle del Piloto) -->
            <aside class="w-full xl:w-[340px] shrink-0">
                <div class="dispatch-card dispatch-side p-5 flex flex-col gap-5 dark:bg-[#0b1728] bg-white border dark:border-[#1e293b] border-slate-200">
                    <!-- Vista por defecto: Sin conductor seleccionado -->
                    <template x-if="!selectedPilot">
                        <div class="flex flex-col h-full py-2">
                            <div class="flex flex-col items-center justify-center text-center p-6 dark:bg-[#070d19]/40 bg-slate-50 border dark:border-slate-800/80 border-slate-200 rounded-2xl min-h-[220px]">
                                <span class="w-12 h-12 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 mb-4 shadow-lg shadow-indigo-600/5">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                    </svg>
                                </span>
                                <h3 class="text-sm font-extrabold dark:text-white text-slate-800">Selecciona un piloto</h3>
                                <p class="text-xs dark:text-slate-400 text-slate-500 max-w-xs mt-1.5 leading-relaxed">El detalle operativo aparecerá aquí con progreso, paradas y acciones de entrega.</p>
                            </div>
                            
                            <!-- Listado rápido de pilotos disponibles -->
                            <div class="w-full mt-6 flex flex-col gap-3">
                                <div class="flex items-center justify-between gap-3 px-1">
                                    <h4 class="text-[10px] font-black text-slate-500 tracking-wider uppercase">Pilotos disponibles</h4>
                                    <span class="text-[10px] font-bold text-indigo-400">{{ count($dispatches) }} en total</span>
                                </div>
                                <div class="flex flex-col gap-2 overflow-y-auto max-h-[300px] pr-1 scrollbar-thin">
                                    @forelse($dispatches as $d)
                                        <div 
                                            wire:click="selectDriver({{ $d['driver_id'] }})"
                                            class="flex items-center justify-between p-3.5 dark:bg-[#070d19]/30 bg-slate-50 border dark:border-slate-800/80 border-slate-200 rounded-2xl cursor-pointer hover:border-indigo-500/40 hover:bg-[#13223f]/10 transition-all duration-300 group"
                                        >
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-9 h-9 rounded-xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-xs font-black text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300 shrink-0">
                                                    {{ strtoupper(substr($d['driver_name'], 0, 2)) }}
                                                </div>
                                                <div class="text-left min-w-0">
                                                    <p class="text-xs font-bold dark:text-white text-slate-800 group-hover:text-indigo-400 transition-colors truncate max-w-[160px]">{{ $d['driver_name'] }}</p>
                                                    <p class="text-[10px] dark:text-slate-400 text-slate-500 truncate max-w-[190px]">{{ $d['truck_name'] }} · {{ $d['route'] }}</p>
                                                    <p class="text-[9px] text-slate-500 truncate max-w-[190px] mt-0.5">{{ $d['dispatch_count'] }} despachos · {{ $d['total_orders'] }} pedidos</p>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold shrink-0 {{ $d['status'] === 'in_progress' ? 'bg-sky-500/10 text-sky-400 border border-sky-500/20' : ($d['status'] === 'pending' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20') }}">
                                                {{ $d['status'] === 'in_progress' ? 'En ruta' : ($d['status'] === 'pending' ? 'Pendiente' : 'Completado') }}
                                            </span>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center text-center p-6 dark:bg-[#070d19]/40 bg-slate-50 border dark:border-slate-800 border-slate-200 rounded-2xl">
                                            <svg class="w-8 h-8 text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v2m4.5 5.5h3m-9 0h.01" />
                                            </svg>
                                            <p class="text-xs font-bold dark:text-slate-300 text-slate-700">No hay pilotos para este filtro</p>
                                            <p class="text-[10px] text-slate-500 mt-0.5">Cambia el estado o crea un nuevo despacho.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Vista del detalle del piloto seleccionado -->
                    <template x-if="selectedPilot">
                        <div class="flex flex-col gap-5">
                            <!-- Ficha del Piloto (Cabecera del Detalle) -->
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-full bg-[#13223f]/80 flex items-center justify-center font-black text-sm text-white" x-text="selectedPilot.driver_initials"></div>
                                    <div class="text-left">
                                        <h3 class="text-sm font-extrabold dark:text-white text-slate-800 leading-tight" x-text="selectedPilot.driver_name"></h3>
                                        <p class="text-xs dark:text-slate-400 text-slate-500 font-medium mt-0.5" x-text="selectedPilot.truck_name"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-emerald-950/80 text-emerald-400 border border-emerald-800/60" x-text="selectedPilot.status === 'in_progress' ? 'En Proceso' : 'Completado'"></span>
                                    <button @click="deselectDriver()" class="text-slate-500 dark:hover:text-white hover:text-slate-900 transition-colors p-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Métricas de la ruta -->
                            <div class="grid grid-cols-4 gap-0 text-center py-4 border-y dark:border-slate-800/60 border-slate-200">
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Entregas</span>
                                    <span class="text-xl font-black dark:text-white text-slate-800 mt-1" x-text="selectedPilot.stats.total"></span>
                                </div>
                                <div class="flex flex-col border-l dark:border-slate-800/60 border-slate-200">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Completadas</span>
                                    <span class="text-xl font-black dark:text-white text-slate-800 mt-1" x-text="selectedPilot.stats.completed"></span>
                                </div>
                                <div class="flex flex-col border-l dark:border-slate-800/60 border-slate-200">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Pendientes</span>
                                    <span class="text-xl font-black dark:text-white text-slate-800 mt-1" x-text="selectedPilot.stats.pending"></span>
                                </div>
                                <div class="flex flex-col border-l dark:border-slate-800/60 border-slate-200">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Devoluciones</span>
                                    <span class="text-xl font-black dark:text-white text-slate-800 mt-1" x-text="selectedPilot.stats.returns"></span>
                                </div>
                            </div>

                            <!-- Botón Ver Despacho -->
                            <a :href="selectedPilot.latest_dispatch_id ? '/admin/dispatches/' + selectedPilot.latest_dispatch_id : (selectedPilot.dispatch_ids && selectedPilot.dispatch_ids.length ? '/admin/dispatches/' + selectedPilot.dispatch_ids[0] : '/admin/dispatches')"
                               class="w-full bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold py-2.5 rounded-xl transition-all duration-300 text-center block">
                                Ver lista de despachos
                            </a>

                            <!-- Progreso de la Ruta -->
                            <div class="flex flex-col gap-2 mt-2">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-bold">Progreso de la ruta</span>
                                    <span class="dark:text-white text-slate-800 font-black" x-text="selectedPilot.progress + '%'"></span>
                                </div>
                                <div class="w-full h-1.5 dark:bg-[#0b1329] bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-violet-600 transition-all duration-700" :style="'width: ' + selectedPilot.progress + '%'"></div>
                                </div>
                            </div>

                            <!-- Listado de Paradas (Vertical Timeline) -->
                            <div class="flex flex-col gap-3 mt-2">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-sm font-extrabold dark:text-white text-slate-800" x-text="'Paradas (' + selectedPilotStops.length + ')'"></h4>
                                    <button class="dark:bg-[#070d19] bg-slate-50 border dark:border-slate-800 border-slate-200 text-[10px] dark:text-slate-300 text-slate-700 px-3 py-1.5 rounded-full font-bold dark:hover:text-white hover:text-slate-900 transition-colors">
                                        ⇅ Orden óptimo
                                    </button>
                                </div>

                                <div class="flex flex-col gap-0 overflow-y-auto max-h-[300px] pr-1 scrollbar-thin">
                                    <template x-for="(stop, idx) in selectedPilotStops" :key="stop.id">
                                        <div 
                                            class="flex gap-4 relative cursor-pointer py-3 border-b dark:border-slate-800/30 border-slate-200 last:border-0"
                                            @click="zoomToStop(stop)"
                                        >
                                            <!-- Indicador de Parada (Sólido con línea) -->
                                            <div class="flex flex-col items-center shrink-0">
                                                <div 
                                                    class="w-7 h-7 rounded-full flex items-center justify-center font-black text-xs z-10 relative"
                                                    :class="stop.status === 'completed' 
                                                        ? 'bg-emerald-500 text-white' 
                                                        : (stop.status === 'returned' 
                                                            ? 'bg-amber-500 text-white' 
                                                            : (stop.number === selectedPilotStops.length 
                                                                ? 'bg-red-500 text-white' 
                                                                : 'bg-violet-500 text-white'))"
                                                >
                                                    <span x-text="stop.status === 'completed' ? '✓' : (stop.number === selectedPilotStops.length ? 'P' : stop.number)"></span>
                                                </div>
                                                <div class="w-[2px] dark:bg-slate-800/80 bg-slate-200 grow my-1 group-last:hidden"></div>
                                            </div>

                                            <!-- Información de la Parada -->
                                            <div class="flex flex-col text-left grow min-w-0">
                                                <div class="flex justify-between items-start gap-2">
                                                    <template x-if="stop.number === selectedPilotStops.length">
                                                        <div class="flex flex-col">
                                                            <p class="text-xs font-bold dark:text-white text-slate-800">Destino</p>
                                                            <p class="text-[10px] dark:text-slate-400 text-slate-500 mt-0.5 truncate max-w-[160px]" x-text="stop.delivery_address"></p>
                                                        </div>
                                                    </template>
                                                    <template x-if="stop.number !== selectedPilotStops.length">
                                                        <p class="text-xs font-bold dark:text-white text-slate-800 max-w-[160px] leading-tight" x-text="stop.delivery_address"></p>
                                                    </template>
                                                    
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <span 
                                                            class="text-[10px] font-bold"
                                                            :class="stop.status === 'completed' 
                                                                ? 'text-emerald-500' 
                                                                : (stop.status === 'returned' 
                                                                    ? 'text-amber-500' 
                                                                    : 'text-violet-500')"
                                                            x-text="stop.status === 'completed' ? 'Completado' : (stop.status === 'returned' ? 'Devuelto' : 'Pendiente')"
                                                        ></span>
                                                        <span class="text-[10px] text-slate-500 font-mono" x-text="formatTime(stop, stop.number - 1)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Leyenda de Estados (Debajo de la lista) -->
                                <div class="flex items-center justify-between pt-2 text-[10px] text-slate-500 font-medium">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <span>Completado</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>
                                        <span>Pendiente</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                        <span>Destino</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span>Devolución</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel de Acciones Rápidas (Estilo capsule premium) -->
                            <template x-if="selectedPilot.status === 'in_progress' || selectedPilot.status === 'completed'">
                                <div class="border-t border-slate-800/60 pt-4 flex flex-col gap-3">
                                    <div class="flex flex-col gap-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <!-- Reportar Devolución -->
                                            <button 
                                                type="button"
                                                @click="reportSelectedStopReturn()"
                                                :disabled="!activeStopId || (selectedStop && (selectedStop.status === 'completed' || selectedStop.status === 'returned'))"
                                                :class="(!activeStopId || (selectedStop && (selectedStop.status === 'completed' || selectedStop.status === 'returned'))) ? 'opacity-40 cursor-not-allowed' : 'active:scale-[0.98]'"
                                                class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-2 transition-all duration-300"
                                            >
                                                Reportar Devolución
                                            </button>

                                            <!-- Cancelar Despacho -->
                                            <button 
                                                type="button"
                                                @click="cancelActiveDispatch()"
                                                class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-all duration-300"
                                            >
                                                Cancelar Despacho
                                            </button>
                                        </div>

                                        <!-- Finalizar Entrega -->
                                        <template x-if="activeStopId && selectedStop && selectedStop.status !== 'completed' && selectedStop.status !== 'returned'">
                                            <button 
                                                type="button"
                                                @click="completeSelectedStop()"
                                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-all duration-300 mt-1"
                                            >
                                                Finalizar Entrega
                                            </button>
                                        </template>

                                        <!-- Liquidar Despacho -->
                                        <template x-if="selectedPilot.status === 'completed'">
                                            <button 
                                                type="button"
                                                @click="finishActiveDispatch()"
                                                class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-all duration-300 mt-1"
                                            >
                                                Liquidar Despacho y Facturar
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- MODAL DE REGISTRO DE DEVOLUCIÓN -->
        <div 
            x-show="showReturnModal" 
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4"
            x-cloak
            x-transition
        >
            <div class="bg-[#0a1120] border border-slate-800 rounded-2xl p-6 shadow-2xl w-full max-w-md text-left flex flex-col gap-5">
                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                        <span>⚠️</span> Reportar Devolución
                    </h3>
                    <button @click="showReturnModal = false" class="text-slate-400 hover:text-white font-black">✕</button>
                </div>

                <div class="flex flex-col gap-4">
                    <template x-if="selectedStop">
                        <div class="bg-[#070d19] p-3 rounded-xl border border-slate-800/80">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Cliente</p>
                            <p class="text-xs font-bold text-white mt-0.5" x-text="selectedStop.customer_name"></p>
                            <p class="text-[10px] text-slate-400 mt-1" x-text="selectedStop.delivery_address"></p>
                        </div>
                    </template>

                    <!-- Formulario de Devolución -->
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-slate-400 font-bold">Producto a devolver</label>
                            <select 
                                wire:model.defer="returnProductId"
                                class="bg-[#070d19] border border-slate-800 rounded-xl text-xs py-2 px-3 text-white focus:border-indigo-500"
                            >
                                <template x-if="selectedStop">
                                    <template x-for="item in selectedStop.items" :key="item.id">
                                        <option :value="item.product_id" x-text="item.product_name + ' (' + item.color_name + ')'"></option>
                                    </template>
                                </template>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-slate-400 font-bold">Cantidad</label>
                            <input 
                                type="number" 
                                step="any"
                                wire:model.defer="returnQuantity" 
                                class="bg-[#070d19] border border-slate-800 rounded-xl text-xs py-2 px-3 text-white focus:border-indigo-500"
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-slate-400 font-bold">Razón de la Devolución</label>
                            <select 
                                wire:model.defer="returnReason"
                                class="bg-[#070d19] border border-slate-800 rounded-xl text-xs py-2 px-3 text-white focus:border-indigo-500"
                            >
                                <option value="El cliente no se encontraba">El cliente no se encontraba</option>
                                <option value="Producto dañado/defectuoso">Producto dañado/defectuoso</option>
                                <option value="Pedido incorrecto">Pedido incorrecto</option>
                                <option value="Cliente rechaza el producto">Cliente rechaza el producto</option>
                                <option value="Otros">Otros (especificar en notas)</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-slate-400 font-bold">Notas adicionales</label>
                            <textarea 
                                wire:model.defer="returnNotes"
                                rows="3"
                                class="bg-[#070d19] border border-slate-800 rounded-xl text-xs py-2 px-3 text-white focus:border-indigo-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
                    <button 
                        @click="showReturnModal = false" 
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition-all duration-300"
                    >
                        Cancelar
                    </button>
                    <button 
                        wire:click="submitReturn()" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all duration-300"
                    >
                        Guardar Devolución
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function realTimeDashboardComponent() {
                return {
                    map: null,
                    mapLayer: 'map', // 'map' or 'satellite'
                    darkLayer: null,
                    satelliteLayer: null,
                    activeMarkers: {},
                    stopMarkers: [],
                    routeLine: null,
                    
                    selectedPilot: null,
                    selectedPilotStops: [],
                    activeStopId: null,
                    selectedStop: null,
                    
                    // Modales
                    showReturnModal: false,
                    
                    // Polling
                    refreshTimer: null,
                    
                    async init() {
                        this.selectedPilot = @json($this->getSelectedDriverDetails());
                        this.selectedPilotStops = @json($this->getSelectedDriverStops());
                        
                        await this.loadLeaflet();
                        this.initMap();
                        
                        // Escuchadores de eventos de Livewire
                        window.addEventListener('dispatch-selected', (e) => {
                            this.selectedPilot = e.detail.details;
                            this.selectedPilotStops = e.detail.stops || [];
                            this.activeStopId = null;
                            this.selectedStop = null;
                            
                            this.renderSelectedRoute(e.detail.locations || [], this.selectedPilotStops);
                        });

                        window.addEventListener('dashboard-filter-changed', (e) => {
                            this.selectedPilot = null;
                            this.selectedPilotStops = [];
                            this.activeStopId = null;
                            this.selectedStop = null;
                            this.clearSelectedRoute();
                            this.updatePilotsMarkers(e.detail.pilots || []);
                        });

                        window.addEventListener('dispatch-cancelled', () => {
                            this.selectedPilot = null;
                            this.selectedPilotStops = [];
                            this.activeStopId = null;
                            this.selectedStop = null;
                            this.clearSelectedRoute();
                            this.loadAllActivePilots();
                        });

                        window.addEventListener('open-return-modal', () => {
                            this.showReturnModal = true;
                        });

                        window.addEventListener('close-return-modal', () => {
                            this.showReturnModal = false;
                        });

                        // Redimensionar mapa al entrar/salir de pantalla completa
                        document.addEventListener('fullscreenchange', () => {
                            if (this.map) {
                                setTimeout(() => this.map.invalidateSize(), 150);
                            }
                        });

                        // Polling para refrescar ubicaciones cada 8 segundos
                        this.refreshTimer = setInterval(() => this.pollUbicaciones(), 8000);
                        this.pollUbicaciones();
                    },

                    async loadLeaflet() {
                        if (window.L) return;
                        const loadStyle = (url, id) => {
                            if (document.getElementById(id)) return Promise.resolve();
                            return new Promise(resolve => {
                                const link = document.createElement('link');
                                link.id = id; link.rel = 'stylesheet'; link.href = url; link.onload = resolve; link.onerror = resolve;
                                document.head.appendChild(link);
                            });
                        };
                        const loadScript = (url, id) => {
                            if (document.getElementById(id)) return Promise.resolve();
                            return new Promise(resolve => {
                                const script = document.createElement('script');
                                script.id = id; script.src = url; script.onload = resolve; script.onerror = resolve;
                                document.head.appendChild(script);
                            });
                        };
                        await loadStyle('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', 'leaflet-css');
                        await loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'leaflet-js');
                    },

                    initMap() {
                        if (this.map) return;
                        
                        // Posicionar por defecto en el centro de Guatemala / Cobán
                        this.map = L.map('dispatch-dashboard-map', { zoomControl: false }).setView([15.47, -90.37], 8);
                        
                        // Capa oscura premium (CARTO Dark Matter)
                        this.darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
                            maxZoom: 20
                        });
                        
                        // Capa satélite premium (Esri World Imagery)
                        this.satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                            maxZoom: 20
                        });
                        
                        // Cargar capa oscura por defecto
                        this.darkLayer.addTo(this.map);
                        
                        // Controles de Zoom en la parte inferior izquierda
                        L.control.zoom({ position: 'bottomleft' }).addTo(this.map);

                        // Si hay un piloto pre-seleccionado, cargar su ruta
                        if (this.selectedPilot) {
                            this.renderSelectedRoute(@json($this->getSelectedDriverLocations()), this.selectedPilotStops);
                        } else {
                            this.loadAllActivePilots();
                        }
                    },

                    setMapLayer(layer) {
                        this.mapLayer = layer;
                        if (layer === 'satellite') {
                            this.map.removeLayer(this.darkLayer);
                            this.satelliteLayer.addTo(this.map);
                        } else {
                            this.map.removeLayer(this.satelliteLayer);
                            this.darkLayer.addTo(this.map);
                        }
                    },

                    toggleFullscreen() {
                        const elem = document.getElementById('dispatch-map-card');
                        if (!elem) return;
                        if (!document.fullscreenElement) {
                            elem.requestFullscreen().catch(err => {
                                console.error(`Error attempting to enable fullscreen: ${err.message}`);
                            });
                        } else {
                            document.exitFullscreen();
                        }
                    },

                    deselectDriver() {
                        this.selectedPilot = null;
                        this.selectedPilotStops = [];
                        this.activeStopId = null;
                        this.selectedStop = null;
                        this.clearSelectedRoute();
                        this.loadAllActivePilots();
                        
                        // Actualizar propiedades del backend a null sin recargar toda la página
                        this.$wire.set('selectedDriverId', null);
                        this.$wire.set('selectedDispatchId', null);
                    },

                    getPilotLocationDetails() {
                        if (!this.selectedPilot) return null;
                        const pilots = @json($this->getActivePilotsLocations());
                        const found = pilots.find(p => p.driver_id === this.selectedPilot.driver_id);
                        return found || { speed: 45, updated_at: 'hace 1 min' };
                    },

                    formatTime(stop, idx) {
                        let totalMinutes = 0;
                        if (stop.status === 'completed') {
                            totalMinutes = 8 * 60 + 15 + (idx * 15);
                        } else {
                            totalMinutes = 10 * 60 + 30 + (idx * 20);
                        }
                        const hrs = Math.floor(totalMinutes / 60);
                        const mins = totalMinutes % 60;
                        return String(hrs).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
                    },

                    getShortAddress(address) {
                        if (!address) return '';
                        return address
                            .replace(/Alta Verapaz/gi, 'A. Verapaz')
                            .replace(/Baja Verapaz/gi, 'B. Verapaz');
                    },

                    // Carga y dibuja todos los pilotos activos en el mapa
                    loadAllActivePilots() {
                        this.clearSelectedRoute();
                        const pilots = @json($this->getActivePilotsLocations());
                        this.updatePilotsMarkers(pilots);
                    },

                    updatePilotsMarkers(pilots) {
                        // Deduplicar pilotos por `driver_id`
                        const seen = {};
                        const uniquePilots = [];
                        pilots.forEach(p => {
                            const key = (p.driver_id !== undefined && p.driver_id !== null) ? String(p.driver_id) : (p.driver_name || '').trim().toLowerCase();
                            if (!key) return;
                            if (!seen[key]) {
                                seen[key] = p;
                                uniquePilots.push(p);
                            } else {
                                const existing = seen[key];
                                if (p.status === 'in_progress' && existing.status !== 'in_progress') {
                                    seen[key] = p;
                                    const idx = uniquePilots.findIndex(x => ((x.driver_id || '').toString() === (existing.driver_id || '').toString()) || (x.driver_name || '').toLowerCase() === (existing.driver_name || '').toLowerCase());
                                    if (idx !== -1) uniquePilots[idx] = p;
                                } else if (p.timestamp && existing.timestamp) {
                                    try {
                                        if (new Date(p.timestamp) > new Date(existing.timestamp)) {
                                            seen[key] = p;
                                            const idx = uniquePilots.findIndex(x => ((x.driver_id || '').toString() === (existing.driver_id || '').toString()) || (x.driver_name || '').toLowerCase() === (existing.driver_name || '').toLowerCase());
                                            if (idx !== -1) uniquePilots[idx] = p;
                                        }
                                    } catch (e) {}
                                }
                            }
                        });

                        pilots = uniquePilots;

                        const activeIds = pilots.map(p => {
                            return (p.driver_id !== undefined && p.driver_id !== null) ? String(p.driver_id) : (p.driver_name || '').trim().toLowerCase();
                        });

                        Object.keys(this.activeMarkers).forEach(id => {
                            if (!activeIds.includes(id)) {
                                this.map.removeLayer(this.activeMarkers[id]);
                                delete this.activeMarkers[id];
                            }
                        });

                        pilots.forEach(p => {
                            const key = (p.driver_id !== undefined && p.driver_id !== null) ? String(p.driver_id) : (p.driver_name || '').trim().toLowerCase();
                            const iconHtml = `
                                <div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;">
                                    <div style="position:relative;">
                                        <div style="position:absolute;width:34px;height:34px;background:rgba(99,102,241,0.3);border-radius:50%;animation:ping 2s infinite;"></div>
                                        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:2px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(99,102,241,0.5);">
                                            <span style="font-size:16px;">🚚</span>
                                        </div>
                                    </div>
                                    <div style="margin-top:4px;padding:2px 8px;background:rgba(15,23,42,0.95);color:white;font-size:9px;font-weight:800;border-radius:6px;white-space:nowrap;border:1px solid rgba(255,255,255,0.12);">
                                        ${p.driver_name ? p.driver_name.split(' ')[0] : ''}
                                    </div>
                                </div>
                            `;

                            const customIcon = L.divIcon({
                                className: '',
                                html: iconHtml,
                                iconSize: [80, 50],
                                iconAnchor: [40, 25]
                            });

                            if (this.activeMarkers[key]) {
                                this.activeMarkers[key].setLatLng([p.lat, p.lng]);
                            } else {
                                const marker = L.marker([p.lat, p.lng], { icon: customIcon })
                                    .addTo(this.map)
                                    .on('click', () => {
                                        this.$wire.selectDriver(p.driver_id);
                                    });
                                this.activeMarkers[key] = marker;
                            }
                        });

                        // Ajustar la vista si no hay piloto seleccionado y hay marcadores
                        if (!this.selectedPilot && pilots.length > 0) {
                            const group = L.featureGroup(Object.values(this.activeMarkers));
                            this.map.fitBounds(group.getBounds().pad(0.2));
                        }
                    },

                    // Dibuja la ruta y las paradas del piloto seleccionado
                    renderSelectedRoute(locations, stops) {
                        this.clearSelectedRoute();
                        
                        // 1. Dibujar línea de recorrido (morada premium idéntica a la foto)
                        const pts = locations.map(l => [l.lat, l.lng]);
                        if (pts.length > 1) {
                            this.routeLine = L.polyline(pts, {
                                color: '#6366f1',
                                weight: 4.5,
                                opacity: 0.95,
                                lineJoin: 'round'
                            }).addTo(this.map);
                        }

                        // 2. Dibujar marcadores de paradas (clientes)
                        const bounds = [];
                        stops.forEach(s => {
                            if (!s.lat || !s.lng) return;
                            
                            const isCompleted = s.status === 'completed';
                            const isReturned = s.status === 'returned';
                            
                            const color = isCompleted 
                                ? '#10b981' // Verde esmeralda
                                : (isReturned 
                                    ? '#f97316' // Naranja devolución
                                    : '#6366f1'); // Violeta/azul
                                    
                            const stopHtml = `
                                <div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;">
                                    <div style="width:24px;height:24px;border-radius:50%;background:${color};border:2px solid white;color:white;font-family:'Outfit',sans-serif;font-weight:900;font-size:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.5);">
                                        ${isCompleted ? '✓' : (s.number === stops.length ? 'P' : s.number)}
                                    </div>
                                    <div style="margin-top:2px;padding:1px 5px;background:rgba(15,23,42,0.85);color:white;font-size:8px;font-weight:bold;border-radius:4px;white-space:nowrap;border:1px solid rgba(255,255,255,0.05);">
                                        ${s.number === stops.length ? 'Destino' : 'P. ' + s.number}
                                    </div>
                                </div>
                            `;

                            const icon = L.divIcon({
                                className: '',
                                html: stopHtml,
                                iconSize: [50, 40],
                                iconAnchor: [25, 20]
                            });

                            const marker = L.marker([s.lat, s.lng], { icon: icon })
                                .addTo(this.map)
                                .on('click', () => {
                                    this.zoomToStop(s);
                                });

                            this.stopMarkers.push(marker);
                            bounds.push([s.lat, s.lng]);
                        });

                        // 3. Dibujar camión en la última posición conocida
                        if (pts.length > 0) {
                            const lastPt = pts[pts.length - 1];
                            const truckHtml = `
                                <div style="position:relative;display:flex;flex-direction:column;align-items:center;">
                                    <div style="position:absolute;width:42px;height:42px;background:rgba(99,102,241,0.4);border-radius:50%;animation:ping 2s infinite;"></div>
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:2.5px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(99,102,241,0.5);">
                                        <span style="font-size:16px;">🚚</span>
                                    </div>
                                </div>
                            `;

                            const truckIcon = L.divIcon({
                                className: '',
                                html: truckHtml,
                                iconSize: [50, 50],
                                iconAnchor: [25, 25]
                            });

                            const marker = L.marker(lastPt, { icon: truckIcon }).addTo(this.map);
                            this.stopMarkers.push(marker);
                            bounds.push(lastPt);
                        }

                        // Enfocar y centrar la ruta completa
                        if (bounds.length > 0) {
                            this.map.fitBounds(bounds, { padding: [50, 50] });
                        }
                    },

                    clearSelectedRoute() {
                        // Limpiar camiones del mapa general
                        Object.values(this.activeMarkers).forEach(m => this.map.removeLayer(m));
                        this.activeMarkers = {};
                        
                        // Limpiar polilíneas y marcadores de paradas
                        if (this.routeLine) {
                            this.map.removeLayer(this.routeLine);
                            this.routeLine = null;
                        }
                        this.stopMarkers.forEach(m => this.map.removeLayer(m));
                        this.stopMarkers = [];
                    },

                    zoomToStop(stop) {
                        this.activeStopId = stop.id;
                        this.selectedStop = stop;
                        
                        if (stop.lat && stop.lng) {
                            this.map.setView([stop.lat, stop.lng], 16, { animate: true, duration: 1.2 });
                        }
                    },

                    // Acciones rápidas de la parada seleccionada en Alpine
                    completeSelectedStop() {
                        if (confirm('¿Estás seguro de marcar esta parada como entregada?')) {
                            this.$wire.completeOrder(this.activeStopId);
                        }
                    },

                    reportSelectedStopReturn() {
                        this.$wire.initReturnModal(this.activeStopId);
                    },

                    getActiveDispatchId() {
                        return this.selectedPilot?.latest_dispatch_id || (this.selectedPilot?.dispatch_ids?.length ? this.selectedPilot.dispatch_ids[0] : null);
                    },

                    finishActiveDispatch() {
                        const dispatchId = this.getActiveDispatchId();
                        if (!dispatchId) {
                            alert('No se encontró un despacho válido para este piloto.');
                            return;
                        }

                        if (confirm('¿Desea liquidar el despacho de este piloto? Se generarán las facturas correspondientes para los pedidos.')) {
                            this.$wire.finishDispatchGlobal(dispatchId);
                        }
                    },

                    cancelActiveDispatch() {
                        const dispatchId = this.getActiveDispatchId();
                        if (!dispatchId) {
                            alert('No se encontró un despacho válido para este piloto.');
                            return;
                        }

                        if (confirm('¡ATENCIÓN! ¿Está seguro de cancelar este despacho? Se revertirá la transferencia de stock de inventario.')) {
                            this.$wire.cancelDispatchGlobal(dispatchId);
                        }
                    },

                    // Polling asíncrono para refrescar las posiciones
                    async pollUbicaciones() {
                        try {
                            const data = await this.$wire.refreshLocations();
                            if (!this.selectedPilot) {
                                this.updatePilotsMarkers(data.pilots);
                            } else {
                                this.renderSelectedRoute(data.selectedLocations, this.selectedPilotStops);
                            }
                        } catch (e) {
                            console.error('[Error polling dispatch locations]', e);
                        }
                    }
                }
            }
        </script>
    @endpush
</x-filament-panels::page>
