<x-filament-panels::page>
    <style>
        .dispatch-page {
            width: 100%;
            max-width: none;
            min-height: calc(100dvh - 7rem);
            padding: 1.5rem;
            color: #f8fafc;
            background:
                radial-gradient(circle at top left, rgba(14, 165, 233, 0.16), transparent 34rem),
                radial-gradient(circle at 78% 12%, rgba(16, 185, 129, 0.12), transparent 30rem),
                linear-gradient(135deg, #07111f 0%, #0b1220 54%, #101827 100%);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.34);
            position: relative;
            overflow: hidden;
        }

        .dispatch-page::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.035) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.68), transparent 72%);
        }

        .dispatch-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .dispatch-title-icon {
            width: 2.75rem;
            height: 2.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.16), rgba(99, 102, 241, 0.18));
            border: 1px solid rgba(125, 211, 252, 0.22);
            color: #67e8f9;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 14px 32px rgba(14, 165, 233, 0.12);
        }

        .dispatch-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
            position: relative;
            z-index: 1;
        }

        .dispatch-kpi {
            min-height: 5.25rem;
            padding: 1rem;
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.92), rgba(15, 23, 42, 0.72));
            border: 1px solid rgba(148, 163, 184, 0.16);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.045);
        }

        .dispatch-main {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(340px, 410px);
            gap: 1rem;
            align-items: start;
            position: relative;
            z-index: 1;
        }

        .dispatch-map {
            height: min(62dvh, 680px) !important;
            min-height: 500px;
            border: 1px solid rgba(125, 211, 252, 0.18) !important;
            border-radius: 16px;
            overflow: hidden;
            background: #020617;
            box-shadow: 0 22px 55px rgba(2, 6, 23, 0.46), inset 0 0 0 1px rgba(255, 255, 255, 0.025);
        }

        .dispatch-map::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 401;
            box-shadow: inset 0 0 80px rgba(2, 6, 23, 0.65);
        }

        .dispatch-side,
        .dispatch-route-summary {
            border: 1px solid rgba(148, 163, 184, 0.16) !important;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.86)) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.045), 0 18px 45px rgba(2, 6, 23, 0.28);
        }

        .dispatch-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            padding: .3rem;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.62);
            backdrop-filter: blur(14px);
        }

        .dispatch-tab {
            min-height: 2.25rem;
            padding: .45rem .8rem;
            font-size: .78rem;
            border-radius: 10px;
        }

        .dispatch-action {
            min-height: 2.75rem;
            border-radius: 14px;
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: white;
            box-shadow: 0 16px 35px rgba(14, 165, 233, 0.2);
        }

        .dispatch-action:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        .dispatch-driver-card {
            background: rgba(2, 6, 23, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 14px;
        }

        .dispatch-driver-card:hover {
            border-color: rgba(34, 211, 238, 0.42);
            background: rgba(15, 23, 42, 0.82);
        }

        .dispatch-empty {
            min-height: 12rem;
            border: 1px dashed rgba(148, 163, 184, 0.2);
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.28);
        }

        @media (max-width: 1180px) {
            .dispatch-main {
                grid-template-columns: 1fr;
            }

            .dispatch-map {
                min-height: 430px;
            }

            .dispatch-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .dispatch-page {
                padding: .85rem;
                border-radius: 12px;
            }

            .dispatch-header {
                align-items: stretch;
                flex-direction: column;
            }

            .dispatch-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div 
        x-data="realTimeDashboardComponent()"
        class="dispatch-page flex flex-col gap-4"
        style="font-family: 'Outfit', sans-serif;"
    >
        <!-- CABECERA: Filtros y Botones -->
        @php
            $stats = $this->getTabsStats();
            $dispatches = $this->getDispatches();
        @endphp
        <div class="dispatch-header">
            <div class="flex items-center gap-4 min-w-0">
                <span class="dispatch-title-icon shrink-0">
                    <x-heroicon-o-map class="w-6 h-6" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-2xl font-black tracking-tight text-white leading-tight">
                        Mapa de Rutas en Tiempo Real
                    </h2>
                    <p class="text-sm text-slate-400 font-medium">Ubicacion de pilotos, progreso de entregas y acciones de ruta en una sola vista.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Filtros Tabificados -->
                <div class="dispatch-tabs">
                    @php
                        $tabs = [
                            'todos' => ['label' => 'Todos', 'color' => 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/20'],
                            'in_progress' => ['label' => 'En Proceso', 'color' => 'bg-blue-600/20 text-blue-400 border border-blue-500/20'],
                            'completed' => ['label' => 'Completados', 'color' => 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/20'],
                            'pending' => ['label' => 'Pendientes', 'color' => 'bg-amber-600/20 text-amber-400 border border-amber-500/20'],
                            'delivered' => ['label' => 'Entregados', 'color' => 'bg-slate-600/20 text-slate-400 border border-slate-500/20'],
                        ];
                    @endphp
                    @foreach($tabs as $key => $t)
                        <button 
                            wire:click="setTab('{{ $key }}')"
                            class="dispatch-tab font-bold transition-all duration-300 flex items-center gap-1.5 border {{ $activeTab === $key ? 'bg-sky-500 text-white border-sky-400 shadow-lg shadow-sky-500/15' : 'bg-transparent text-slate-400 border-transparent hover:text-white hover:bg-slate-800/80' }}"
                        >
                            {{ $t['label'] }}
                            <span class="px-1.5 py-0.5 rounded-md text-[10px] font-black {{ $activeTab === $key ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-300' }}">
                                {{ $stats[$key] ?? 0 }}
                            </span>
                        </button>
                    @endforeach
                </div>

                <!-- Botones Accionadores -->
                <a href="{{ \App\Filament\Resources\DispatchResource::getUrl('index') }}"
                   class="px-4 py-2.5 rounded-[14px] border border-slate-600/70 bg-slate-950/40 text-slate-100 font-bold text-xs transition-all duration-300 flex items-center gap-2 hover:border-sky-400/60 hover:bg-slate-900/90">
                    <x-heroicon-o-table-cells class="w-4 h-4 text-sky-300" />
                    Ver Tabla
                </a>

                <a href="{{ \App\Filament\Resources\DispatchResource::getUrl('create') }}" 
                   class="dispatch-action px-4 py-2.5 border border-sky-300/30 font-bold text-xs transition-all duration-300 flex items-center gap-2">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    Nuevo Despacho
                </a>
            </div>
        </div>

        <div class="dispatch-kpis">
            <div class="dispatch-kpi">
                <p class="text-[11px] font-bold text-slate-400">Despachos</p>
                <div class="mt-2 flex items-end justify-between gap-3">
                    <p class="text-2xl font-black text-white">{{ $stats['todos'] ?? 0 }}</p>
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-sky-300" />
                </div>
                <p class="mt-2 text-[11px] font-semibold text-sky-300">Registros visibles</p>
            </div>
            <div class="dispatch-kpi">
                <p class="text-[11px] font-bold text-slate-400">En ruta</p>
                <div class="mt-2 flex items-end justify-between gap-3">
                    <p class="text-2xl font-black text-white">{{ $stats['in_progress'] ?? 0 }}</p>
                    <x-heroicon-o-truck class="w-5 h-5 text-blue-300" />
                </div>
                <p class="mt-2 text-[11px] font-semibold text-blue-300">Pilotos transmitiendo</p>
            </div>
            <div class="dispatch-kpi">
                <p class="text-[11px] font-bold text-slate-400">Completados</p>
                <div class="mt-2 flex items-end justify-between gap-3">
                    <p class="text-2xl font-black text-white">{{ $stats['completed'] ?? 0 }}</p>
                    <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-300" />
                </div>
                <p class="mt-2 text-[11px] font-semibold text-emerald-300">Listos para liquidar</p>
            </div>
            <div class="dispatch-kpi">
                <p class="text-[11px] font-bold text-slate-400">Pendientes</p>
                <div class="mt-2 flex items-end justify-between gap-3">
                    <p class="text-2xl font-black text-white">{{ $stats['pending'] ?? 0 }}</p>
                    <x-heroicon-o-clock class="w-5 h-5 text-amber-300" />
                </div>
                <p class="mt-2 text-[11px] font-semibold text-amber-300">Sin iniciar o en cola</p>
            </div>
        </div>

        <!-- CONTENIDO PRINCIPAL: MAPA (70%) + DETALLES (30%) -->
        <div class="dispatch-main">
            <!-- COLUMNA MAPA (7 Columns) -->
            <div class="flex flex-col gap-4 relative min-w-0">
                <!-- CONTENEDOR MAPA -->
                <div class="dispatch-map relative w-full shadow-xl">
                    <div id="dispatch-dashboard-map" class="absolute inset-0 z-0" wire:ignore></div>

                    <div class="absolute left-4 top-4 z-[999] flex flex-wrap items-center gap-2">
                        <div class="rounded-xl border border-slate-700/70 bg-slate-950/88 px-3 py-2 shadow-2xl backdrop-blur-md">
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Cobertura GPS</p>
                            <p class="text-xs font-bold text-white">Guatemala / Rutas activas</p>
                        </div>
                        <div class="rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-3 py-2 shadow-2xl backdrop-blur-md">
                            <p class="text-[10px] font-black uppercase tracking-wider text-emerald-300">{{ $stats['in_progress'] ?? 0 }} en ruta</p>
                            <p class="text-[10px] font-semibold text-emerald-100/80">Actualizacion cada 8s</p>
                        </div>
                    </div>

                    <!-- Overlay de Piloto Activo en la esquina inferior izquierda -->
                    <template x-if="selectedPilot">
                        <div class="absolute bottom-4 left-4 z-[999] bg-slate-950/95 backdrop-blur-md p-4 rounded-xl border border-slate-800 shadow-2xl flex items-center gap-4 transition-all duration-500 max-w-sm">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600/10 border border-indigo-500/30 flex items-center justify-center text-xl animate-pulse">
                                🚚
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-indigo-400 tracking-wider uppercase" x-text="selectedPilot.dispatch_number"></span>
                                <span class="text-sm font-bold text-white leading-tight" x-text="selectedPilot.driver_name"></span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-[10px] text-emerald-400 font-bold" x-text="selectedPilot.status === 'in_progress' ? 'En ruta' : 'Completado'"></span>
                                    <span class="text-[10px] text-slate-500">|</span>
                                    <span class="text-[10px] text-slate-400 font-medium" x-text="'Vehículo: ' + selectedPilot.truck_name"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- RESUMEN HORIZONTAL DE LA RUTA (Abajo del mapa) -->
                <template x-if="selectedPilotStops.length > 0">
                    <div class="dispatch-route-summary p-5 shadow-xl flex flex-col gap-4">
                        <h4 class="text-xs font-black text-slate-400 tracking-widest uppercase flex items-center gap-1.5">
                            🏁 Resumen de la ruta de <span class="text-indigo-400 font-bold" x-text="selectedPilot.driver_name"></span>
                        </h4>
                        
                        <!-- Timeline horizontal de paradas -->
                        <div class="relative flex items-center justify-between w-full mt-4 px-6 overflow-x-auto pb-4">
                            <!-- Track Line detrás -->
                            <div class="absolute left-10 right-10 h-[2px] bg-slate-800 z-0" style="top: 14px;"></div>
                            
                            <template x-for="(stop, idx) in selectedPilotStops" :key="stop.id">
                                <div class="flex flex-col items-center z-10 min-w-[120px] text-center group cursor-pointer" @click="zoomToStop(stop)">
                                    <!-- Círculo del Punto -->
                                    <div 
                                        class="w-7 h-7 rounded-full flex items-center justify-center font-black text-[11px] transition-all duration-300 border-2 shadow-lg"
                                        :class="stop.status === 'completed' 
                                            ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500 hover:bg-emerald-500 hover:text-white' 
                                            : (stop.status === 'returned' 
                                                ? 'bg-amber-500/20 text-amber-400 border-amber-500 hover:bg-amber-500 hover:text-white' 
                                                : 'bg-indigo-950 text-indigo-400 border-indigo-700 hover:bg-indigo-600 hover:text-white')"
                                    >
                                        <span x-text="stop.status === 'completed' ? '✓' : stop.number"></span>
                                    </div>
                                    <span class="text-[10px] font-bold text-white mt-2 leading-tight block group-hover:text-indigo-400" x-text="stop.customer_name.substring(0, 15) + '...'"></span>
                                    <span class="text-[9px] font-medium text-slate-400 leading-tight block mt-0.5" x-text="stop.delivery_address.substring(0, 20) + '...'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- COLUMNA DETALLES & ACCIONES (3 Columns) -->
            <div class="flex flex-col gap-4 min-w-0">
                <!-- PANEL LATERAL DE DETALLES DEL PILOTO -->
                <div class="dispatch-side p-5 shadow-xl flex flex-col gap-5" style="min-height: min(62dvh, 680px);">
                    <template x-if="!selectedPilot">
                        <div class="flex flex-col h-full py-2">
                            <div class="dispatch-empty flex flex-col items-center justify-center text-center px-6">
                                <span class="dispatch-title-icon mb-4">
                                    <x-heroicon-o-cursor-arrow-rays class="w-6 h-6" />
                                </span>
                                <h3 class="text-sm font-extrabold text-white">Selecciona un piloto</h3>
                                <p class="text-xs text-slate-400 max-w-xs mt-1.5 leading-relaxed">El detalle operativo aparecera aqui con progreso, paradas y acciones de entrega.</p>
                            </div>
                            
                            <!-- Listado rápido de pilotos disponibles -->
                            <div class="w-full mt-8 flex flex-col gap-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-[10px] font-black text-slate-400 tracking-wider uppercase text-left">Pilotos disponibles</h4>
                                    <span class="text-[10px] font-bold text-slate-500">{{ count($dispatches) }} rutas</span>
                                </div>
                                <div class="flex flex-col gap-2 overflow-y-auto max-h-[250px]">
                                    @forelse($dispatches as $d)
                                        <div 
                                            wire:click="selectDispatch({{ $d['id'] }})"
                                            class="dispatch-driver-card flex items-center justify-between p-3 cursor-pointer transition-all duration-300 hover:-translate-y-0.5 group"
                                        >
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-sky-500/10 border border-sky-400/20 flex items-center justify-center text-xs font-black text-sky-300 group-hover:bg-sky-500 group-hover:text-white transition-all duration-300">
                                                    {{ strtoupper(substr($d['driver_name'], 0, 2)) }}
                                                </div>
                                                <div class="text-left min-w-0">
                                                    <p class="text-xs font-bold text-white">{{ $d['driver_name'] }}</p>
                                                    <p class="text-[10px] text-slate-400 truncate max-w-[190px]">{{ $d['truck_name'] }} / {{ $d['route'] }}</p>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $d['status'] === 'in_progress' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/20' : 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/20' }}">
                                                {{ $d['status'] === 'in_progress' ? 'En ruta' : 'Terminado' }}
                                            </span>
                                        </div>
                                    @empty
                                        <div class="dispatch-empty flex flex-col items-center justify-center text-center p-5">
                                            <x-heroicon-o-inbox class="w-7 h-7 text-slate-500" />
                                            <p class="mt-2 text-xs font-bold text-slate-300">No hay pilotos para este filtro</p>
                                            <p class="mt-1 text-[11px] text-slate-500">Cambia el estado o crea un nuevo despacho.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="selectedPilot">
                        <div class="flex flex-col gap-6">
                            <!-- Ficha del Piloto -->
                            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-500 border-2 border-white/20 flex items-center justify-center font-black text-sm text-white shadow-lg shadow-indigo-600/20" x-text="selectedPilot.driver_initials"></div>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-white" x-text="selectedPilot.driver_name"></h3>
                                        <p class="text-[11px] text-slate-400 font-medium" x-text="selectedPilot.truck_name"></p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wider uppercase" 
                                      :class="selectedPilot.status === 'in_progress' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/20' : 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/20'"
                                      x-text="selectedPilot.status === 'in_progress' ? 'En Proceso' : 'Completado'"></span>
                            </div>

                            <!-- Métricas de la ruta -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-950/80 border border-slate-800/80 p-3 rounded-xl">
                                    <span class="text-[10px] text-slate-500 font-bold block">Entregas</span>
                                    <span class="text-base font-black text-white block mt-0.5" x-text="selectedPilot.stats.total"></span>
                                </div>
                                <div class="bg-slate-950/80 border border-slate-800/80 p-3 rounded-xl">
                                    <span class="text-[10px] text-emerald-500 font-bold block">Completadas</span>
                                    <span class="text-base font-black text-emerald-400 block mt-0.5" x-text="selectedPilot.stats.completed"></span>
                                </div>
                                <div class="bg-slate-950/80 border border-slate-800/80 p-3 rounded-xl">
                                    <span class="text-[10px] text-indigo-500 font-bold block">Pendientes</span>
                                    <span class="text-base font-black text-indigo-400 block mt-0.5" x-text="selectedPilot.stats.pending"></span>
                                </div>
                                <div class="bg-slate-950/80 border border-slate-800/80 p-3 rounded-xl">
                                    <span class="text-[10px] text-amber-500 font-bold block">Devoluciones</span>
                                    <span class="text-base font-black text-amber-400 block mt-0.5" x-text="selectedPilot.stats.returns"></span>
                                </div>
                            </div>

                            <!-- Botón Ver Despacho -->
                            <a :href="'/admin/dispatches/' + selectedPilot.id"
                               class="w-full text-center border border-indigo-500/30 hover:border-indigo-500 hover:bg-indigo-600/10 text-indigo-400 font-bold text-xs py-2 rounded-xl transition-all duration-300">
                                Ver lista de despachos
                            </a>

                            <!-- Progreso -->
                            <div class="flex flex-col gap-1.5 border-t border-slate-800 pt-4">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400 font-bold">Progreso de la ruta</span>
                                    <span class="text-white font-black" x-text="selectedPilot.progress + '%'"></span>
                                </div>
                                <div class="w-full h-2.5 bg-slate-950 rounded-full overflow-hidden border border-slate-800/50">
                                    <div class="h-full bg-gradient-to-r from-indigo-600 to-violet-500 transition-all duration-700" :style="'width: ' + selectedPilot.progress + '%'"></div>
                                </div>
                            </div>

                            <!-- Listado de Paradas (Vertical Timeline) -->
                            <div class="flex flex-col gap-3 border-t border-slate-800 pt-4">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-[11px] font-black text-slate-400 tracking-wider uppercase" x-text="'Paradas (' + selectedPilotStops.length + ')'"></h4>
                                    <button class="text-[10px] text-indigo-400 font-bold hover:underline">⇅ Orden óptimo</button>
                                </div>

                                <div class="flex flex-col gap-3 overflow-y-auto max-h-[280px] pr-1">
                                    <template x-for="stop in selectedPilotStops" :key="stop.id">
                                        <div 
                                            class="flex gap-3 relative group"
                                            :class="activeStopId === stop.id ? 'bg-slate-950/60 p-2.5 rounded-xl border border-indigo-500/20' : ''"
                                        >
                                            <!-- Indicador de línea de tiempo conector vertical -->
                                            <div class="flex flex-col items-center">
                                                <div 
                                                    class="w-5 h-5 rounded-full flex items-center justify-center font-black text-[9px] transition-all duration-300 border-2"
                                                    :class="stop.status === 'completed' 
                                                        ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500' 
                                                        : (stop.status === 'returned' 
                                                            ? 'bg-amber-500/20 text-amber-400 border-amber-500' 
                                                            : 'bg-indigo-950 text-indigo-400 border-indigo-700')"
                                                >
                                                    <span x-text="stop.status === 'completed' ? '✓' : stop.number"></span>
                                                </div>
                                                <div class="w-[1.5px] bg-slate-800 grow my-1 group-last:hidden"></div>
                                            </div>

                                            <!-- Información de la parada -->
                                            <div class="flex flex-col text-left grow cursor-pointer" @click="zoomToStop(stop)">
                                                <div class="flex justify-between items-start gap-1">
                                                    <p class="text-xs font-extrabold text-white leading-tight" x-text="stop.customer_name"></p>
                                                    <span 
                                                        class="px-1.5 py-0.5 rounded text-[8px] font-black whitespace-nowrap"
                                                        :class="stop.status === 'completed' 
                                                            ? 'bg-emerald-500/20 text-emerald-400' 
                                                            : (stop.status === 'returned' 
                                                                ? 'bg-amber-500/20 text-amber-400' 
                                                                : 'bg-indigo-950 text-indigo-400')"
                                                        x-text="stop.status === 'completed' ? 'Completado' : (stop.status === 'returned' ? 'Devuelto' : 'Pendiente')"
                                                    ></span>
                                                </div>
                                                <p class="text-[10px] text-slate-400 leading-snug mt-0.5" x-text="stop.delivery_address"></p>
                                                <p class="text-[9px] text-slate-500 mt-1 font-bold" x-text="'Monto: Q ' + parseFloat(stop.total).toLocaleString('es-GT', {minimumFractionDigits: 2})"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Panel de Acciones Rápidas (Solo si el despacho está en proceso) -->
                            <template x-if="selectedPilot.status === 'in_progress' || selectedPilot.status === 'completed'">
                                <div class="border-t border-slate-800 pt-4 flex flex-col gap-3">
                                    <h4 class="text-[11px] font-black text-slate-400 tracking-wider uppercase text-left">Acciones rápidas</h4>
                                    
                                    <div class="flex flex-col gap-2">
                                        <!-- Completar parada seleccionada -->
                                        <template x-if="activeStopId && selectedStop && selectedStop.status !== 'completed' && selectedStop.status !== 'returned'">
                                            <button 
                                                @click="completeSelectedStop()"
                                                class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/10 transition-all duration-300"
                                            >
                                                <span>✓</span> Finalizar Entrega (P. <span x-text="selectedStop.number"></span>)
                                            </button>
                                        </template>

                                        <!-- Reportar Devolución para la parada activa -->
                                        <template x-if="activeStopId && selectedStop && selectedStop.status !== 'completed' && selectedStop.status !== 'returned'">
                                            <button 
                                                @click="reportSelectedStopReturn()"
                                                class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-amber-600/10 transition-all duration-300"
                                            >
                                                <span>⚠️</span> Reportar Devolución (P. <span x-text="selectedStop.number"></span>)
                                            </button>
                                        </template>

                                        <!-- Finalizar Despacho (Solo si no está entregado) -->
                                        <template x-if="selectedPilot.status === 'completed'">
                                            <button 
                                                @click="finishActiveDispatch()"
                                                class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/10 transition-all duration-300"
                                            >
                                                <span>🏠</span> Liquidar Despacho y Facturar
                                            </button>
                                        </template>

                                        <!-- Cancelar Despacho -->
                                        <button 
                                            @click="cancelActiveDispatch()"
                                            class="w-full border border-rose-500/30 hover:bg-rose-500/10 text-rose-400 font-bold text-xs py-2 rounded-xl flex items-center justify-center gap-2 transition-all duration-300"
                                        >
                                            <span>✕</span> Cancelar Despacho
                                        </button>
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
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl w-full max-w-md text-left flex flex-col gap-5">
                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                        <span>⚠️</span> Reportar Devolución
                    </h3>
                    <button @click="showReturnModal = false" class="text-slate-400 hover:text-white font-black">✕</button>
                </div>

                <div class="flex flex-col gap-4">
                    <template x-if="selectedStop">
                        <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
                            <p class="text-[10px] text-slate-500 font-bold">Cliente</p>
                            <p class="text-xs font-bold text-white" x-text="selectedStop.customer_name"></p>
                            <p class="text-[9px] text-slate-400 mt-1" x-text="selectedStop.delivery_address"></p>
                        </div>
                    </template>

                    <!-- Formulario de Devolución vinculando las variables de Livewire -->
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-slate-400 font-bold">Producto a devolver</label>
                            <select 
                                wire:model.defer="returnProductId"
                                class="bg-slate-950 border border-slate-800 rounded-xl text-xs py-2 px-3 text-white focus:border-indigo-500"
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
                                class="bg-slate-950 border border-slate-800 rounded-xl text-xs py-2 px-3 text-white focus:border-indigo-500"
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-slate-400 font-bold">Razón de la Devolución</label>
                            <select 
                                wire:model.defer="returnReason"
                                class="bg-slate-950 border border-slate-800 rounded-xl text-xs py-2 px-3 text-white focus:border-indigo-500"
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
                                class="bg-slate-950 border border-slate-800 rounded-xl text-xs py-2 px-3 text-white focus:border-indigo-500"
                                placeholder="Escribe detalles adicionales..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
                    <button 
                        @click="showReturnModal = false" 
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        wire:click="submitReturn()" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg transition-colors"
                    >
                        Reportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets e Inicialización del Mapa en JavaScript/Alpine.js -->
    @push('scripts')
        <script>
            window.realTimeDashboardComponent = function() {
                return {
                    map: null,
                    activeMarkers: {},
                    routeLine: null,
                    stopMarkers: [],
                    
                    // State del piloto seleccionado
                    selectedPilot: @json($this->getSelectedDispatchDetails()),
                    selectedPilotStops: [],
                    activeStopId: null,
                    selectedStop: null,
                    
                    // Modales
                    showReturnModal: false,
                    
                    // Polling
                    refreshTimer: null,
                    
                    async init() {
                        this.selectedPilot = @json($this->getSelectedDispatchDetails());
                        this.selectedPilotStops = @json($this->getSelectedDispatchStops());
                        
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
                        
                        // CARTO Dark Matter - Mapa de estilo oscuro premium e idéntico a la foto
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
                            maxZoom: 20
                        }).addTo(this.map);
                        
                        L.control.zoom({ position: 'bottomright' }).addTo(this.map);

                        // Si hay un piloto pre-seleccionado, cargar su ruta
                        if (this.selectedPilot) {
                            this.renderSelectedRoute(@json($this->getSelectedDispatchLocations()), this.selectedPilotStops);
                        } else {
                            this.loadAllActivePilots();
                        }
                    },

                    // Carga y dibuja todos los pilotos activos en el mapa
                    loadAllActivePilots() {
                        this.clearSelectedRoute();
                        const pilots = @json($this->getActivePilotsLocations());
                        this.updatePilotsMarkers(pilots);
                    },

                    updatePilotsMarkers(pilots) {
                        // Limpiar marcadores que ya no estén activos
                        const activeIds = pilots.map(p => p.dispatch_id);
                        Object.keys(this.activeMarkers).forEach(id => {
                            if (!activeIds.includes(parseInt(id))) {
                                this.map.removeLayer(this.activeMarkers[id]);
                                delete this.activeMarkers[id];
                            }
                        });

                        pilots.forEach(p => {
                            const iconHtml = `
                                <div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;">
                                    <div style="position:relative;">
                                        <div style="position:absolute;width:34px;height:34px;background:rgba(139,92,246,0.3);border-radius:50%;animation:ping 2s infinite;"></div>
                                        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#8b5cf6,#6366f1);border:2px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(139,92,246,0.5);">
                                            <span style="font-size:16px;">🚚</span>
                                        </div>
                                    </div>
                                    <div style="margin-top:4px;padding:2px 8px;background:rgba(15,23,42,0.9);color:white;font-size:9px;font-weight:800;border-radius:6px;white-space:nowrap;border:1px solid rgba(255,255,255,0.1);">
                                        ${p.driver_name.split(' ')[0]}
                                    </div>
                                </div>
                            `;

                            const customIcon = L.divIcon({
                                className: '',
                                html: iconHtml,
                                iconSize: [80, 50],
                                iconAnchor: [40, 25]
                            });

                            if (this.activeMarkers[p.dispatch_id]) {
                                this.activeMarkers[p.dispatch_id].setLatLng([p.lat, p.lng]);
                            } else {
                                const marker = L.marker([p.lat, p.lng], { icon: customIcon })
                                    .addTo(this.map)
                                    .on('click', () => {
                                        this.$wire.selectDispatch(p.dispatch_id);
                                    });
                                this.activeMarkers[p.dispatch_id] = marker;
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
                        
                        // 1. Dibujar línea de recorrido (morada premium)
                        const pts = locations.map(l => [l.lat, l.lng]);
                        if (pts.length > 1) {
                            this.routeLine = L.polyline(pts, {
                                color: '#8b5cf6', // Violeta brillante
                                weight: 4,
                                opacity: 0.9,
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
                                    ? '#f59e0b' // Ámbar de novedad
                                    : '#8b5cf6'); // Violeta de ruta
                                    
                            const stopHtml = `
                                <div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;">
                                    <div style="width:24px;height:24px;border-radius:50%;background:${color};border:2px solid white;color:white;font-family:'Outfit',sans-serif;font-weight:900;font-size:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.5);">
                                        ${isCompleted ? '✓' : s.number}
                                    </div>
                                    <div style="margin-top:2px;padding:1px 5px;background:rgba(15,23,42,0.85);color:white;font-size:8px;font-weight:bold;border-radius:4px;white-space:nowrap;border:1px solid rgba(255,255,255,0.05);">
                                        P. ${s.number}
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
                                    <div style="position:absolute;width:40px;height:40px;background:rgba(99,102,241,0.3);border-radius:50%;animation:ping 2s infinite;"></div>
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#ff4b5c,#ffac41);border:2px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(255,75,92,0.4);">
                                        <span style="font-size:18px;">🚚</span>
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

                    finishActiveDispatch() {
                        if (confirm('¿Desea liquidar el despacho de este piloto? Se generarán las facturas correspondientes para los pedidos.')) {
                            this.$wire.finishDispatchGlobal(this.selectedPilot.id);
                        }
                    },

                    cancelActiveDispatch() {
                        if (confirm('¡ATENCIÓN! ¿Está seguro de cancelar este despacho? Se revertirá la transferencia de stock de inventario.')) {
                            this.$wire.cancelDispatchGlobal(this.selectedPilot.id);
                        }
                    },

                    // Polling asíncrono para refrescar las posiciones
                    async pollUbicaciones() {
                        try {
                            const data = await this.$wire.refreshLocations();
                            if (!this.selectedPilot) {
                                this.updatePilotsMarkers(data.pilots);
                            } else {
                                // Si hay piloto seleccionado, actualizar su trayecto y camión
                                this.renderSelectedRoute(data.selectedLocations, this.selectedPilotStops);
                            }
                        } catch (e) {
                            console.error('[Error polling dispatch locations]', e);
                        }
                    }
                }
            };
        </script>
    @endpush
</x-filament-panels::page>
