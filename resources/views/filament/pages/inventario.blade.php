<x-filament-panels::page>
    <style>
        .premium-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .dark .premium-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .premium-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            border-color: var(--p-1);
        }
        .stat-icon-wrap {
            position: relative;
            z-index: 10;
        }
        .bg-mesh {
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            filter: blur(40px);
            opacity: 0.1;
            z-index: 0;
        }
    </style>

    <div class="space-y-8">
        <!-- TOP KPI ROW -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- SKU TOTAL -->
            <div class="premium-card p-6 rounded-[2rem] relative overflow-hidden group">
                <div class="bg-mesh bg-indigo-500"></div>
                <div class="flex items-center space-x-4 relative z-10">
                    <div class="p-4 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                        <x-heroicon-o-squares-2x2 class="w-8 h-8" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Variedad Total</p>
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white leading-none">
                            {{ number_format($this->totalProducts) }}
                            <span class="text-xs font-bold text-gray-400 ml-1">SKUs</span>
                        </h3>
                    </div>
                </div>
            </div>

            <!-- STOCK CRITICO -->
            <a href="{{ $this->getMovementsUrl('out') }}" class="premium-card p-6 rounded-[2rem] relative overflow-hidden group border-l-4 {{ $this->criticalStockCount > 0 ? 'border-l-rose-500' : 'border-l-emerald-500' }}">
                <div class="bg-mesh bg-rose-500"></div>
                <div class="flex items-center space-x-4 relative z-10">
                    <div class="p-4 {{ $this->criticalStockCount > 0 ? 'bg-rose-500/10 text-rose-600' : 'bg-emerald-500/10 text-emerald-600' }} rounded-2xl">
                        <x-heroicon-o-exclamation-triangle class="w-8 h-8 animate-pulse" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">En Riesgo</p>
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white leading-none">{{ number_format($this->criticalStockCount) }}</h3>
                    </div>
                </div>
            </a>

            <!-- PEDIDOS PENDIENTES -->
            <div class="premium-card p-6 rounded-[2rem] relative overflow-hidden group">
                <div class="bg-mesh bg-amber-500"></div>
                <div class="flex items-center space-x-4 relative z-10">
                    <div class="p-4 bg-amber-500/10 text-amber-600 rounded-2xl">
                        <x-heroicon-o-clock class="w-8 h-8" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pendientes</p>
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white leading-none">{{ number_format($this->pendingOrdersCount) }}</h3>
                    </div>
                </div>
            </div>

            <!-- ACTIVIDAD 24H -->
            <div class="premium-card p-6 rounded-[2rem] relative overflow-hidden group">
                <div class="bg-mesh bg-sky-500"></div>
                <div class="flex items-center space-x-4 relative z-10">
                    <div class="p-4 bg-sky-500/10 text-sky-600 rounded-2xl">
                        <x-heroicon-o-arrow-path class="w-8 h-8" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Movs 24h</p>
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white leading-none">{{ number_format($this->last24hMovements) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- MAIN AREA -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- PRODUCT TYPE ACCESS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-indigo-600 to-violet-800 p-[2px] rounded-[2.5rem] shadow-2xl group transition-all hover:scale-[1.02]">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.4rem] p-8 h-full relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 text-indigo-500/10 group-hover:text-indigo-500/20 transition-colors">
                                <x-heroicon-o-cube-transparent class="w-48 h-48" />
                            </div>
                            <div class="relative z-10">
                                <span class="px-3 py-1 bg-indigo-500/10 text-indigo-600 text-[10px] font-black uppercase rounded-full">Almacén de Ventas</span>
                                <h4 class="text-2xl font-black text-gray-900 dark:text-white mt-4 mb-2">Producto Terminado</h4>
                                <div class="flex items-baseline space-x-2 mb-8">
                                    <span class="text-5xl font-black text-indigo-600">{{ number_format($this->finishedProducts) }}</span>
                                    <span class="text-gray-400 font-bold uppercase text-[10px]">Modelos</span>
                                </div>
                                <a href="{{ $this->getFinishedProductsIndexUrl() }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-2xl text-xs font-black shadow-xl shadow-indigo-500/40 hover:bg-indigo-700 transition-all">
                                    Explorar Inventario
                                    <x-heroicon-m-arrow-small-right class="w-4 h-4 ml-2" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-500 to-teal-700 p-[2px] rounded-[2.5rem] shadow-2xl group transition-all hover:scale-[1.02]">
                        <div class="bg-white dark:bg-gray-900 rounded-[2.4rem] p-8 h-full relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 text-emerald-500/10 group-hover:text-emerald-500/20 transition-colors">
                                <x-heroicon-o-beaker class="w-48 h-48" />
                            </div>
                            <div class="relative z-10">
                                <span class="px-3 py-1 bg-emerald-500/10 text-emerald-600 text-[10px] font-black uppercase rounded-full">Suministros</span>
                                <h4 class="text-2xl font-black text-gray-900 dark:text-white mt-4 mb-2">Materias Primas</h4>
                                <div class="flex items-baseline space-x-2 mb-8">
                                    <span class="text-5xl font-black text-emerald-600">{{ number_format($this->rawMaterials) }}</span>
                                    <span class="text-gray-400 font-bold uppercase text-[10px]">Activas</span>
                                </div>
                                <a href="{{ $this->getRawMaterialsIndexUrl() }}" class="inline-flex items-center px-6 py-3 bg-emerald-600 text-white rounded-2xl text-xs font-black shadow-xl shadow-emerald-500/40 hover:bg-emerald-700 transition-all">
                                    Gestionar Materiales
                                    <x-heroicon-m-arrow-small-right class="w-4 h-4 ml-2" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RECENT MOVEMENTS -->
                <div class="premium-card rounded-[2.5rem] overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/30">
                        <div>
                            <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest flex items-center">
                                <div class="w-2 h-2 bg-indigo-600 rounded-full mr-3 animate-ping"></div>
                                Monitor de Actividad (Kardex)
                            </h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Últimos 8 movimientos registrados</p>
                        </div>
                        <a href="{{ $this->getKardexUrl() }}" class="px-4 py-2 bg-gray-900 text-white dark:bg-white dark:text-gray-900 rounded-xl text-[10px] font-black uppercase hover:scale-105 transition-transform">Ver Todo</a>
                    </div>
                    
                    <div class="p-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($this->getRecentMovements() as $m)
                                        <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition-colors">
                                            <td class="py-5 px-4">
                                                <div class="flex items-center space-x-4">
                                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: {{ $m['type_bg'] }}">
                                                        <span class="text-lg" style="color: {{ $m['type_color'] }}">●</span>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $m['description'] }}</p>
                                                        <div class="flex items-center space-x-2 text-[10px] text-gray-400 font-bold uppercase">
                                                            <span>{{ $m['user'] }}</span>
                                                            <span>•</span>
                                                            <span>{{ $m['time_ago'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-5 px-4 text-right">
                                                <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest" style="color: {{ $m['type_color'] }}; background-color: {{ $m['type_bg'] }}">
                                                    {{ $m['type_label'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR -->
            <div class="lg:col-span-4 space-y-8">
                <!-- QUICK ACTIONS -->
                <div class="bg-gray-950 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                    <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-indigo-600/20 blur-[80px] rounded-full group-hover:bg-indigo-600/30 transition-colors"></div>
                    
                    <h3 class="text-xl font-black mb-8 relative z-10 flex items-center">
                        <x-heroicon-o-command-line class="w-6 h-6 mr-3 text-indigo-500" />
                        Operaciones
                    </h3>

                    <div class="space-y-3 relative z-10">
                        <a href="{{ $this->getMovementCreateUrl('in') }}" class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all group/btn">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 bg-emerald-500/20 text-emerald-400 rounded-xl group-hover/btn:scale-110 transition-transform">
                                    <x-heroicon-o-plus-circle class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold tracking-tight">Nuevo Ingreso</span>
                            </div>
                            <x-heroicon-m-chevron-right class="w-4 h-4 text-white/20" />
                        </a>
                        
                        <a href="{{ $this->getMovementCreateUrl('out') }}" class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all group/btn">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 bg-rose-500/20 text-rose-400 rounded-xl group-hover/btn:scale-110 transition-transform">
                                    <x-heroicon-o-minus-circle class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold tracking-tight">Salida Directa</span>
                            </div>
                            <x-heroicon-m-chevron-right class="w-4 h-4 text-white/20" />
                        </a>

                        <a href="{{ $this->getMovementCreateUrl('transfer') }}" class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all group/btn">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 bg-indigo-500/20 text-indigo-400 rounded-xl group-hover/btn:scale-110 transition-transform">
                                    <x-heroicon-o-arrows-right-left class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold tracking-tight">Traslado Interno</span>
                            </div>
                            <x-heroicon-m-chevron-right class="w-4 h-4 text-white/20" />
                        </a>
                    </div>
                </div>

                <!-- WAREHOUSES -->
                <div class="premium-card rounded-[2.5rem] p-8">
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Ubicaciones</h3>
                        <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded-full uppercase">Activas</span>
                    </div>

                    <div class="space-y-8">
                        @foreach($this->getWarehouseSummaries() as $wh)
                            <div class="relative">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 rounded-2xl {{ $wh['is_factory'] ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }} flex items-center justify-center mr-4">
                                            <x-heroicon-o-building-office-2 class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-black text-gray-900 dark:text-white leading-tight">{{ $wh['name'] }}</h5>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">{{ $wh['is_factory'] ? 'Fábrica' : 'Bodega' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-2xl">
                                        <p class="text-[8px] font-black text-gray-400 uppercase mb-1">Materias Primas</p>
                                        <p class="text-sm font-black text-gray-900 dark:text-white">{{ number_format($wh['raw_total']) }}</p>
                                    </div>
                                    <div class="bg-indigo-50/50 dark:bg-indigo-500/10 p-3 rounded-2xl">
                                        <p class="text-[8px] font-black text-indigo-600 dark:text-indigo-400 uppercase mb-1">Terminados</p>
                                        <p class="text-sm font-black text-gray-900 dark:text-white">{{ number_format($wh['finished_total']) }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
