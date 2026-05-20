<x-filament-panels::page>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        .dispatch-map-page {
            width: 100%;
            max-width: none;
            font-family: 'Outfit', sans-serif;
            position: relative;
        }

        /* Contenedor del mapa: 620px de alto, overflow:hidden, NUNCA contiene el resumen */
        .dispatch-map-page .rtd-map-box {
            height: 620px !important;
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            border: 1px solid;
            transition: all 0.3s ease;
        }
        .dark .dispatch-map-page .rtd-map-box,
        [data-theme="dark"] .dispatch-map-page .rtd-map-box {
            background: #0b1728;
            border-color: #1e293b;
            box-shadow: 0 10px 30px rgba(0,0,0,0.45);
        }
        .dispatch-map-page .rtd-map-box {
            background: #ffffff;
            border-color: #e2e8f0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.07);
        }

        /* Resumen de ruta horizontal con scroll */
        .dispatch-map-page .rtd-summary-scroll {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 0.75rem;
            scrollbar-width: thin;
        }
        .dispatch-map-page .rtd-summary-scroll::-webkit-scrollbar {
            height: 6px;
        }
        .dispatch-map-page .rtd-summary-scroll::-webkit-scrollbar-track {
            background: rgba(15,23,42,0.05);
            border-radius: 99px;
        }
        .dark .dispatch-map-page .rtd-summary-scroll::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }
        .dispatch-map-page .rtd-summary-scroll::-webkit-scrollbar-thumb {
            background: rgba(99,102,241,0.25);
            border-radius: 99px;
        }
        .dispatch-map-page .rtd-summary-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(99,102,241,0.5);
        }

        /* Lista de paradas vertical con scroll maximo de 280px */
        .dispatch-map-page .rtd-stops-scroll {
            max-height: 280px;
            overflow-y: auto;
            padding-right: 0.5rem;
            scrollbar-width: thin;
        }
        .dispatch-map-page .rtd-stops-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .dispatch-map-page .rtd-stops-scroll::-webkit-scrollbar-track {
            background: rgba(15,23,42,0.05);
            border-radius: 99px;
        }
        .dark .dispatch-map-page .rtd-stops-scroll::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }
        .dispatch-map-page .rtd-stops-scroll::-webkit-scrollbar-thumb {
            background: rgba(99,102,241,0.25);
            border-radius: 99px;
        }
        .dispatch-map-page .rtd-stops-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(99,102,241,0.5);
        }

        /* Custom Scrollbar general */
        .dispatch-map-page .scrollbar-thin::-webkit-scrollbar { height: 5px; width: 5px; }
        .dispatch-map-page .scrollbar-thin::-webkit-scrollbar-track { background: rgba(15,23,42,0.05); border-radius: 99px; }
        .dispatch-map-page .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.25); border-radius: 99px; }
        .dispatch-map-page .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #7c3aed; }

        /* Zoom Control Leaflet premium */
        .dispatch-map-page .leaflet-control-zoom {
            border: 1px solid rgba(255,255,255,0.08) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.25) !important;
        }
        .dispatch-map-page .leaflet-control-zoom a {
            width: 32px !important;
            height: 32px !important;
            background: #0f172a !important;
            color: #94a3b8 !important;
            border: none !important;
            border-bottom: 1px solid rgba(255,255,255,0.06) !important;
            line-height: 32px !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            transition: all 0.2s ease !important;
        }
        .dispatch-map-page .leaflet-control-zoom a:hover { background: #7c3aed !important; color: #fff !important; }

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

        <div class="grid xl:grid-cols-[minmax(0,1fr)_360px] gap-5 items-start w-full">
            <!-- COLUMNA IZQUIERDA -->
            <div class="flex flex-col gap-5 min-w-0">
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
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-lg shadow-violet-600/25 transition-all active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            + Nuevo Despacho
                        </a>
                    </div>
                </div>

                {{-- Filter tabs + map controls --}}
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex flex-wrap items-center gap-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-1">
                        @php
                            $tabs = [
                                'todos'       => ['label' => 'Todos',          'color' => 'bg-violet-500'],
                                'in_progress' => ['label' => 'En Proceso',     'color' => 'bg-sky-500'],
                                'completed'   => ['label' => 'Completados',    'color' => 'bg-emerald-500'],
                                'pending'     => ['label' => 'Pendientes',     'color' => 'bg-amber-500'],
                                'delivered'   => ['label' => 'Con Devolución', 'color' => 'bg-orange-500'],
                            ];
                        @endphp
                        @foreach ($tabs as $key => $t)
                            <button wire:click="setTab('{{ $key }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold transition-all duration-200
                                    {{ $activeTab === $key ? 'bg-violet-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                {{ $t['label'] }}
                                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[9px] font-black text-white {{ $t['color'] }}">{{ $stats[$key] ?? 0 }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-1 gap-1">
                            <span class="text-[10px] text-slate-400 font-bold px-2">Vista</span>
                            <button type="button" @click="setMapLayer('map')"
                                :class="mapLayer === 'map' ? 'bg-violet-600 text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                class="px-3 py-1 rounded-lg text-[10px] font-bold transition-all">Mapa</button>
                            <button type="button" @click="setMapLayer('satellite')"
                                :class="mapLayer === 'satellite' ? 'bg-violet-600 text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                class="px-3 py-1 rounded-lg text-[10px] font-bold transition-all">Satélite</button>
                        </div>
                        <button type="button" @click="toggleFullscreen()"
                            class="w-8 h-8 flex items-center justify-center rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4M16 4h4v4M4 16v4h4M16 20h4v-4"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- MAP BOX — overflow:hidden stops here, nothing else inside --}}
                <div id="dispatch-map-card" class="rtd-map-box w-full shadow-2xl bg-slate-950">
                    <div id="dispatch-dashboard-map" class="absolute inset-0 z-0" wire:ignore></div>
                    <template x-if="selectedPilot">
                        <div class="absolute bottom-4 left-4 z-[900] flex items-center gap-3 bg-slate-950/90 backdrop-blur-md border border-white/10 rounded-2xl px-4 py-3 shadow-2xl min-w-[240px]">
                            <div class="w-9 h-9 rounded-full bg-[#13223f] border border-slate-700/50 flex items-center justify-center text-xs font-black text-white shrink-0" x-text="selectedPilot.driver_initials"></div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-white" x-text="selectedPilot.driver_name + ' (' + selectedPilot.truck_name + ')'"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span class="text-[10px] text-emerald-400 font-bold" x-text="selectedPilot.status === 'in_progress' ? 'En ruta' : 'Completado'"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="'Velocidad: ' + (getPilotLocationDetails() ? getPilotLocationDetails().speed : '45') + ' km/h'"></p>
                                <p class="text-[10px] text-slate-400" x-text="'Última actualización: ' + (getPilotLocationDetails() ? getPilotLocationDetails().updated_at : 'hace 1 min')"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Route summary — below map, outside overflow:hidden --}}
                <template x-if="selectedPilotStops.length > 0">
                    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                        <h5 class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-2 mb-3">
                            <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            Resumen de la ruta de <span class="text-violet-500 ml-1" x-text="selectedPilot ? selectedPilot.driver_name : ''"></span>
                        </h5>
                        <div class="rtd-summary-scroll">
                            <template x-for="(stop, idx) in selectedPilotStops" :key="stop.id">
                                <div class="flex items-center shrink-0">
                                    <div class="flex flex-col items-center min-w-[100px] text-center cursor-pointer group" @click="zoomToStop(stop)">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center font-black text-xs text-white shadow-md transition-transform group-hover:scale-110"
                                            :class="stop.status === 'completed' ? 'bg-emerald-500' : (stop.status === 'returned' ? 'bg-amber-500' : (stop.number === selectedPilotStops.length ? 'bg-red-500' : 'bg-violet-600'))">
                                            <span x-text="stop.status === 'completed' ? '✓' : (stop.number === selectedPilotStops.length ? 'P' : stop.number)"></span>
                                        </div>
                                        <div class="mt-1.5 flex flex-col items-center gap-0.5">
                                            <span class="text-[10px] font-semibold text-slate-700 dark:text-slate-300 max-w-[90px] leading-tight text-center line-clamp-2"
                                                x-text="stop.number === selectedPilotStops.length ? 'Destino' : getShortAddress(stop.delivery_address)"></span>
                                            <span class="text-[9px] font-bold"
                                                :class="stop.status === 'completed' ? 'text-emerald-500' : (stop.status === 'returned' ? 'text-amber-500' : 'text-violet-500')"
                                                x-text="stop.status === 'completed' ? 'Completado' : (stop.status === 'returned' ? 'Devuelto' : 'Pendiente')"></span>
                                            <span class="text-[9px] text-slate-400 dark:text-slate-500 font-mono" x-text="formatTime(stop, idx)"></span>
                                        </div>
                                    </div>
                                    <template x-if="idx < selectedPilotStops.length - 1">
                                        <div class="h-[2px] w-8 shrink-0 border-t-2 border-dashed mb-6"
                                            :class="(stop.status === 'completed' && selectedPilotStops[idx+1].status === 'completed') ? 'border-emerald-500' : 'border-slate-300 dark:border-slate-700'"></div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- ════════════════════════════════════ RIGHT COLUMN ══ --}}
            <aside class="w-full xl:w-[360px] xl:sticky xl:top-[4.5rem] self-start shrink-0">
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

                    {{-- Panel header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Detalle del Piloto</h3>
                        <template x-if="selectedPilot">
                            <button @click="deselectDriver()" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </template>
                    </div>

                    {{-- No pilot selected --}}
                    <template x-if="!selectedPilot">
                        <div class="p-5 flex flex-col gap-4">
                            <div class="flex flex-col items-center justify-center text-center py-8 px-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                                <div class="w-12 h-12 rounded-2xl bg-violet-600/10 border border-violet-500/20 flex items-center justify-center text-violet-500 mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                                </div>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Selecciona un piloto</p>
                                <p class="text-xs text-slate-500 mt-1 max-w-[200px] leading-relaxed">Haz clic en un marcador del mapa o en un piloto de la lista.</p>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Pilotos disponibles</p>
                                    <span class="text-[10px] font-bold text-violet-500">{{ count($dispatches) }} total</span>
                                </div>
                                <div class="flex flex-col gap-2">
                                    @forelse ($dispatches as $d)
                                        <div wire:click="selectDriver({{ $d['driver_id'] }})"
                                             class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 cursor-pointer hover:border-violet-400 dark:hover:border-violet-500 hover:bg-violet-50/50 dark:hover:bg-violet-950/30 transition-all group">
                                             <div class="flex items-center gap-2.5 min-w-0">
                                                 <div class="w-8 h-8 rounded-lg bg-[#13223f] border border-slate-700/50 flex items-center justify-center text-[11px] font-black text-white shrink-0 transition-all">
                                                     {{ strtoupper(substr($d['driver_name'], 0, 2)) }}
                                                 </div>
                                                 <div class="min-w-0">
                                                     <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $d['driver_name'] }}</p>
                                                     <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ $d['truck_name'] }}</p>
                                                 </div>
                                             </div>
                                             <span class="text-[9px] font-bold px-2 py-0.5 rounded-full shrink-0 {{ $d['status'] === 'in_progress' ? 'bg-sky-500/10 text-sky-500 border border-sky-500/20' : ($d['status'] === 'pending' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20') }}">
                                                 {{ $d['status'] === 'in_progress' ? 'En ruta' : ($d['status'] === 'pending' ? 'Pendiente' : 'Completado') }}
                                             </span>
                                        </div>
                                    @empty
                                         <div class="text-center py-6 text-xs text-slate-400 dark:text-slate-500">No hay pilotos para este filtro.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Pilot selected --}}
                    <template x-if="selectedPilot">
                        <div class="flex flex-col">

                            {{-- Identity --}}
                            <div class="flex items-center gap-3 px-5 py-4">
                                <div class="w-11 h-11 rounded-full bg-[#13223f] border border-slate-700/50 flex items-center justify-center font-black text-sm text-white shrink-0" x-text="selectedPilot.driver_initials"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-extrabold text-slate-900 dark:text-white truncate" x-text="selectedPilot.driver_name"></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="selectedPilot.truck_name"></p>
                                </div>
                                <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-lg uppercase tracking-wide"
                                    :class="selectedPilot.status === 'in_progress' ? 'bg-[#10b981]/10 text-[#10b981] border border-[#10b981]/20' : 'bg-sky-500/10 text-sky-500 border border-sky-500/20'"
                                    x-text="selectedPilot.status === 'in_progress' ? 'En Proceso' : 'Completado'"></span>
                            </div>

                            {{-- Metrics --}}
                            <div class="grid grid-cols-4 border-y border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20">
                                <div class="flex flex-col items-center py-3 px-1 text-center">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Entregas</span>
                                    <span class="text-xl font-black text-slate-900 dark:text-white mt-0.5" x-text="selectedPilot.stats.total"></span>
                                </div>
                                <div class="flex flex-col items-center py-3 px-1 text-center border-l border-slate-100 dark:border-slate-800">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Compl.</span>
                                    <span class="text-xl font-black text-emerald-500 mt-0.5" x-text="selectedPilot.stats.completed"></span>
                                </div>
                                <div class="flex flex-col items-center py-3 px-1 text-center border-l border-slate-100 dark:border-slate-800">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Pend.</span>
                                    <span class="text-xl font-black text-violet-500 mt-0.5" x-text="selectedPilot.stats.pending"></span>
                                </div>
                                <div class="flex flex-col items-center py-3 px-1 text-center border-l border-slate-100 dark:border-slate-800">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Devol.</span>
                                    <span class="text-xl font-black text-orange-500 mt-0.5" x-text="selectedPilot.stats.returns"></span>
                                </div>
                            </div>

                            <div class="p-5 flex flex-col gap-4">

                                {{-- Ver lista --}}
                                <a :href="selectedPilot.latest_dispatch_id ? '/admin/dispatches/' + selectedPilot.latest_dispatch_id : '/admin/dispatches'"
                                   class="block w-full text-center py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold transition-all active:scale-[0.98] shadow-lg shadow-violet-600/20">
                                    Ver lista de despachos
                                </a>

                                {{-- Progress --}}
                                <div>
                                    <div class="flex justify-between items-center text-xs mb-1.5">
                                        <span class="font-bold text-slate-500 dark:text-slate-400">Progreso de la ruta</span>
                                        <span class="font-black text-slate-900 dark:text-white" x-text="selectedPilot.progress + '%'"></span>
                                    </div>
                                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-violet-600 rounded-full transition-all duration-700" :style="'width:' + selectedPilot.progress + '%'"></div>
                                    </div>
                                </div>

                                {{-- Stops list --}}
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-extrabold text-slate-900 dark:text-white" x-text="'Paradas (' + selectedPilotStops.length + ')'"></h4>
                                        <button class="text-[10px] font-bold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-700 rounded-full px-3 py-1 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                                            ⇅ Orden óptimo
                                        </button>
                                    </div>
                                    <div class="rtd-stops-scroll flex flex-col">
                                        <template x-for="(stop, idx) in selectedPilotStops" :key="stop.id">
                                            <div class="flex gap-3 cursor-pointer py-2.5 border-b border-slate-100 dark:border-slate-800 last:border-0 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-lg px-1 -mx-1 transition-colors"
                                                @click="zoomToStop(stop)">
                                                <div class="flex flex-col items-center shrink-0 pt-0.5">
                                                    <div class="w-6 h-6 rounded-full flex items-center justify-center font-black text-[10px] text-white shadow"
                                                        :class="stop.status === 'completed' ? 'bg-emerald-500' : (stop.status === 'returned' ? 'bg-amber-500' : (stop.number === selectedPilotStops.length ? 'bg-red-500' : 'bg-violet-500'))">
                                                        <span x-text="stop.status === 'completed' ? '✓' : (stop.number === selectedPilotStops.length ? 'P' : stop.number)"></span>
                                                    </div>
                                                    <template x-if="idx < selectedPilotStops.length - 1">
                                                        <div class="w-px flex-1 min-h-[16px] bg-slate-200 dark:bg-slate-700 mt-1"></div>
                                                    </template>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-tight"
                                                            x-text="stop.number === selectedPilotStops.length ? 'Destino — ' + stop.delivery_address : stop.delivery_address"></p>
                                                        <div class="flex items-center gap-1.5 shrink-0">
                                                            <span class="text-[9px] font-bold"
                                                                :class="stop.status === 'completed' ? 'text-emerald-500' : (stop.status === 'returned' ? 'text-amber-500' : 'text-violet-500')"
                                                                x-text="stop.status === 'completed' ? 'Completado' : (stop.status === 'returned' ? 'Devuelto' : 'Pendiente')"></span>
                                                            <span class="text-[9px] text-slate-400 font-mono" x-text="formatTime(stop, stop.number - 1)"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Legend --}}
                                <div class="flex items-center justify-between text-[9px] text-slate-400 dark:text-slate-500 font-bold border-t border-slate-100 dark:border-slate-800 pt-2">
                                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Completado</span>
                                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-violet-500"></span>Pendiente</span>
                                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span>Destino</span>
                                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Devolución</span>
                                </div>

                                {{-- Quick actions --}}
                                <template x-if="selectedPilot.status === 'in_progress' || selectedPilot.status === 'completed'">
                                    <div class="flex flex-col gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Acciones rápidas</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="reportSelectedStopReturn()"
                                                :disabled="!activeStopId || (selectedStop && (selectedStop.status === 'completed' || selectedStop.status === 'returned'))"
                                                class="flex items-center justify-center gap-1.5 py-2.5 px-2 rounded-xl text-[10px] font-bold transition-all shadow-sm"
                                                :class="(!activeStopId || (selectedStop && (selectedStop.status === 'completed' || selectedStop.status === 'returned')))
                                                    ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed border border-slate-200 dark:border-slate-700'
                                                    : 'bg-amber-500 hover:bg-amber-600 text-white shadow-amber-500/20 active:scale-95'">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M5.07 19H19a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.27 16A2 2 0 005.07 19z"/></svg>
                                                Reportar Devolución
                                            </button>
                                            <button type="button" @click="cancelActiveDispatch()"
                                                class="flex items-center justify-center gap-1.5 py-2.5 px-2 rounded-xl bg-red-500 hover:bg-red-600 text-white text-[10px] font-bold transition-all active:scale-95 shadow-sm shadow-red-500/20">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Cancelar Despacho
                                            </button>
                                        </div>
                                        <template x-if="activeStopId && selectedStop && selectedStop.status !== 'completed' && selectedStop.status !== 'returned'">
                                            <button type="button" @click="completeSelectedStop()"
                                                class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition-all active:scale-95 shadow-md shadow-emerald-500/20">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Finalizar Entrega
                                            </button>
                                        </template>
                                        <template x-if="selectedPilot.status === 'completed'">
                                            <button type="button" @click="finishActiveDispatch()"
                                                class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold transition-all active:scale-95 shadow-md shadow-violet-600/20">
                                                Liquidar Despacho y Facturar
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </aside>
        </div>

        {{-- RETURN MODAL --}}
        <div x-show="showReturnModal" x-cloak x-transition
             class="fixed inset-0 z-[99999] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-2xl w-full max-w-md flex flex-col gap-5">
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2"><span>⚠️</span> Reportar Devolución</h3>
                    <button @click="showReturnModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white font-black text-lg leading-none">✕</button>
                </div>
                <div class="flex flex-col gap-4">
                    <template x-if="selectedStop">
                        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Cliente</p>
                            <p class="text-xs font-bold text-slate-900 dark:text-white mt-0.5" x-text="selectedStop.customer_name"></p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="selectedStop.delivery_address"></p>
                        </div>
                    </template>
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Producto a devolver</label>
                            <select wire:model.defer="returnProductId" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs py-2 px-3 text-slate-900 dark:text-white focus:border-violet-500 outline-none">
                                <template x-if="selectedStop">
                                    <template x-for="item in selectedStop.items" :key="item.id">
                                        <option :value="item.product_id" x-text="item.product_name + ' (' + item.color_name + ')'"></option>
                                    </template>
                                </template>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Cantidad</label>
                            <input type="number" step="any" wire:model.defer="returnQuantity" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs py-2 px-3 text-slate-900 dark:text-white focus:border-violet-500 outline-none"/>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Razón de la Devolución</label>
                            <select wire:model.defer="returnReason" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs py-2 px-3 text-slate-900 dark:text-white focus:border-violet-500 outline-none">
                                <option value="El cliente no se encontraba">El cliente no se encontraba</option>
                                <option value="Producto dañado/defectuoso">Producto dañado/defectuoso</option>
                                <option value="Pedido incorrecto">Pedido incorrecto</option>
                                <option value="Cliente rechaza el producto">Cliente rechaza el producto</option>
                                <option value="Otros">Otros (especificar en notas)</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Notas adicionales</label>
                            <textarea wire:model.defer="returnNotes" rows="3" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs py-2 px-3 text-slate-900 dark:text-white focus:border-violet-500 outline-none resize-none"></textarea>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800 pt-4">
                    <button @click="showReturnModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition-all">Cancelar</button>
                    <button wire:click="submitReturn()" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-violet-600/25">Guardar Devolución</button>
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
