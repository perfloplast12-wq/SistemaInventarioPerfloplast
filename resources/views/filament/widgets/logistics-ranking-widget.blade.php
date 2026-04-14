<x-filament-widgets::widget>
    <div class="flex flex-col h-full bg-white dark:bg-[#111827] rounded-b-xl border-x border-b border-[#e2e8f0] dark:border-white/10" style="min-height:355px;">
        <div class="flex-1 overflow-y-auto p-4 sm:p-5 custom-scrollbar">
            @php $operators = $this->getOperatorsData(); @endphp
            
            @if(empty($operators))
                <div class="flex flex-col items-center justify-center h-full opacity-50 py-16">
                    <svg class="w-12 h-12 mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p class="text-[11px] uppercase font-bold tracking-widest text-center text-slate-500">Sin datos de logística<br>en este periodo</p>
                </div>
            @else
                <div class="flex flex-col gap-3">
                    @foreach($operators as $op)
                        <div class="group flex flex-row items-center justify-between p-2.5 sm:p-3 rounded-lg border border-slate-100 dark:border-white/5 bg-white dark:bg-white/5 shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-500/30 transition-all duration-300 relative overflow-hidden">
                            
                            <!-- Decoration Line -->
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                            <div class="flex flex-row items-center gap-3 pl-1 flex-1">
                                <!-- Avatar -->
                                <div class="relative flex-shrink-0">
                                    <img src="{{ $op['avatar'] }}" alt="{{ $op['name'] }}" class="w-9 h-9 rounded-full object-cover shadow-sm ring-2 ring-white dark:ring-gray-900 bg-slate-50">
                                    <div class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border border-white dark:border-gray-900 {{ $op['status'] === 'En Ruta' ? 'bg-amber-500 shadow-[0_0_6px_rgba(245,158,11,0.6)] animate-[pulse_2s_cubic-bezier(0.4,0,0.6,1)_infinite]' : 'bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.4)]' }}"></div>
                                </div>
                                
                                <!-- Driver Info -->
                                <div class="flex flex-col justify-center">
                                    <h4 class="text-[12px] font-bold text-slate-900 dark:text-slate-100 leading-tight tracking-tight">{{ $op['name'] }}</h4>
                                    <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8.5px] font-black uppercase tracking-widest {{ $op['status'] === 'En Ruta' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' }}">
                                            {{ $op['status'] }}
                                        </span>
                                        <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 truncate">{{ $op['last_date'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats right -->
                            <div class="flex flex-col items-end flex-shrink-0 pr-1 ml-2">
                                <div class="text-[16px] font-black text-indigo-600 dark:text-indigo-400 leading-none mb-1.5 tracking-tighter">
                                    {{ $op['dispatches'] }}
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest ml-0.5">Vjs</span>
                                </div>
                                <!-- Subtle progress -->
                                <div class="flex items-center gap-2 w-16 sm:w-20" title="{{ $op['performance'] }}% del mejor logístico">
                                    <div class="flex-1 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-indigo-400 to-indigo-600 rounded-full" style="width: {{ $op['performance'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        
        @if(!empty($operators))
            <div class="px-5 py-3 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 flex items-center justify-between rounded-b-xl">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Activos: {{ count($operators) }}</span>
                <a href="{{ route('filament.admin.resources.dispatches.index') }}" class="text-[11px] font-black text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 uppercase tracking-wider flex items-center gap-1 transition-colors group">
                    Tablero Logístico
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
