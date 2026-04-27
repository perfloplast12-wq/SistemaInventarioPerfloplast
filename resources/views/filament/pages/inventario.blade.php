<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Dashboard Header: KPIs Premium -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total SKU Card -->
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center space-x-4 transition-all hover:shadow-md hover:-translate-y-1">
                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                    <x-heroicon-o-squares-2x2 class="w-8 h-8" />
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Variedad</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($this->totalProducts) }} <span class="text-xs font-normal text-gray-400">SKUs</span></h3>
                </div>
            </div>

            <!-- Critical Stock Card -->
            <a href="{{ $this->getMovementsUrl('out') }}" class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center space-x-4 transition-all hover:shadow-md hover:-translate-y-1 group">
                <div class="p-4 {{ $this->criticalStockCount > 5 ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400' : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' }} rounded-xl">
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8" />
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest group-hover:text-rose-500 transition-colors">Stock Crítico</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($this->criticalStockCount) }}</h3>
                </div>
            </a>

            <!-- Pending Orders Card -->
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center space-x-4">
                <div class="p-4 bg-amber-50 dark:bg-amber-900/30 rounded-xl text-amber-600 dark:text-amber-400">
                    <x-heroicon-o-clock class="w-8 h-8" />
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Pedidos Pendientes</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($this->pendingOrdersCount) }}</h3>
                </div>
            </div>

            <!-- Activity 24h -->
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center space-x-4">
                <div class="p-4 bg-sky-50 dark:bg-sky-900/30 rounded-xl text-sky-600 dark:text-sky-400">
                    <x-heroicon-o-arrow-path class="w-8 h-8" />
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Actividad 24h</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($this->last24hMovements) }} <span class="text-xs font-normal text-gray-400">movs</span></h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content Area (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Quick Access Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Finished Products Box -->
                    <div class="bg-gradient-to-br from-indigo-600 to-violet-700 p-1 rounded-3xl shadow-xl overflow-hidden group">
                        <div class="bg-white dark:bg-gray-900 rounded-[1.4rem] p-6 h-full relative">
                            <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <x-heroicon-o-cube-transparent class="w-32 h-32" />
                            </div>
                            <h4 class="text-sm font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter mb-4">Producto Terminado</h4>
                            <div class="flex justify-between items-end">
                                <div>
                                    <span class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ number_format($this->finishedProducts) }}</span>
                                    <p class="text-xs text-gray-500 mt-1">Modelos en catálogo</p>
                                </div>
                                <a href="{{ $this->getFinishedProductsIndexUrl() }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all">Ver Lista</a>
                            </div>
                        </div>
                    </div>

                    <!-- Raw Materials Box -->
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-1 rounded-3xl shadow-xl overflow-hidden group">
                        <div class="bg-white dark:bg-gray-900 rounded-[1.4rem] p-6 h-full relative">
                            <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <x-heroicon-o-beaker class="w-32 h-32" />
                            </div>
                            <h4 class="text-sm font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-tighter mb-4">Materias Primas</h4>
                            <div class="flex justify-between items-end">
                                <div>
                                    <span class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ number_format($this->rawMaterials) }}</span>
                                    <p class="text-xs text-gray-500 mt-1">Materiales activos</p>
                                </div>
                                <a href="{{ $this->getRawMaterialsIndexUrl() }}" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all">Ver Lista</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Timeline -->
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                        <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest flex items-center">
                            <x-heroicon-o-list-bullet class="w-4 h-4 mr-2 text-indigo-600" />
                            Actividad Reciente (Kardex)
                        </h3>
                        <a href="{{ $this->getKardexUrl() }}" class="text-[10px] font-bold text-indigo-600 uppercase hover:underline">Ver Historial Completo</a>
                    </div>
                    
                    <div class="p-2">
                        @php $movements = $this->getRecentMovements(); @endphp
                        <table class="w-full text-left">
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @forelse($movements as $m)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="py-4 px-4">
                                            <div class="flex items-center space-x-3">
                                                <span class="flex-shrink-0 w-2 h-2 rounded-full" style="background-color: {{ $m['type_color'] }}"></span>
                                                <div class="flex flex-col">
                                                    <span class="text-[13px] font-bold text-gray-900 dark:text-white line-clamp-1">{{ $m['description'] }}</span>
                                                    <span class="text-[10px] text-gray-400 uppercase font-medium">{{ $m['user'] }} • {{ $m['time_ago'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <span class="px-3 py-1 rounded-lg text-[11px] font-black" style="color: {{ $m['type_color'] }}; background-color: {{ $m['type_bg'] }}">
                                                {{ $m['type_label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="py-10 text-center text-gray-400 text-sm">No hay movimientos registrados hoy</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar Area (1/3) -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Action Center -->
                <div class="bg-gray-900 rounded-3xl p-6 text-white shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-600/20 blur-[60px] rounded-full"></div>
                    <h3 class="text-lg font-black mb-4 relative z-10">Centro de Control</h3>
                    <div class="grid grid-cols-1 gap-3 relative z-10">
                        <a href="{{ $this->getMovementCreateUrl('in') }}" class="flex items-center justify-between p-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg group-hover:scale-110 transition-transform">
                                    <x-heroicon-o-plus-circle class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold">Ingreso de Stock</span>
                            </div>
                            <x-heroicon-m-chevron-right class="w-4 h-4 text-white/30" />
                        </a>
                        
                        <a href="{{ $this->getMovementCreateUrl('out') }}" class="flex items-center justify-between p-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-rose-500/20 text-rose-400 rounded-lg group-hover:scale-110 transition-transform">
                                    <x-heroicon-o-minus-circle class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold">Salida / Merma</span>
                            </div>
                            <x-heroicon-m-chevron-right class="w-4 h-4 text-white/30" />
                        </a>

                        <a href="{{ $this->getMovementCreateUrl('transfer') }}" class="flex items-center justify-between p-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-indigo-500/20 text-indigo-400 rounded-lg group-hover:scale-110 transition-transform">
                                    <x-heroicon-o-arrows-right-left class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold">Traslado Bodegas</span>
                            </div>
                            <x-heroicon-m-chevron-right class="w-4 h-4 text-white/30" />
                        </a>
                        
                        <a href="{{ $this->getReturnsUrl() }}" class="flex items-center justify-between p-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-amber-500/20 text-amber-400 rounded-lg group-hover:scale-110 transition-transform">
                                    <x-heroicon-o-arrow-uturn-left class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold">Devoluciones</span>
                            </div>
                            <x-heroicon-m-chevron-right class="w-4 h-4 text-white/30" />
                        </a>
                    </div>
                </div>

                <!-- Warehouse Distribution -->
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Bodegas Activas</h3>
                    <div class="space-y-6">
                        @foreach($this->getWarehouseSummaries() as $wh)
                            <div class="group">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-xl {{ $wh['is_factory'] ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }} flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                            <x-heroicon-o-home-modern class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-black text-gray-900 dark:text-white">{{ $wh['name'] }}</h5>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tight">{{ $wh['is_factory'] ? 'Centro de Producción' : 'Punto de Almacén' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 mt-4">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded-xl border border-gray-100/50 dark:border-gray-700/50">
                                        <p class="text-[9px] font-black text-gray-400 uppercase leading-none mb-1">Materias Primas</p>
                                        <span class="text-sm font-black text-gray-900 dark:text-white">{{ number_format($wh['raw_total']) }}</span>
                                    </div>
                                    <div class="bg-indigo-50/50 dark:bg-indigo-900/20 p-2 rounded-xl border border-indigo-100/30 dark:border-indigo-800/30">
                                        <p class="text-[9px] font-black text-indigo-400 uppercase leading-none mb-1 text-indigo-600 dark:text-indigo-400">Terminados</p>
                                        <span class="text-sm font-black text-gray-900 dark:text-white">{{ number_format($wh['finished_total']) }}</span>
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
