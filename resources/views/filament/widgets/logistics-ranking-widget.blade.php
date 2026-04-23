<x-filament-widgets::widget>
    <div class="flex flex-col h-full bg-white dark:bg-[#111827] rounded-b-xl border-x border-b border-[#e2e8f0] dark:border-white/10" style="min-height:300px;">
        <div class="flex-1 overflow-y-auto p-3 sm:p-5 custom-scrollbar">
            @php $operators = $this->getOperatorsData(); @endphp
            
            @if(empty($operators))
                <div class="flex flex-col items-center justify-center h-full opacity-50 py-12">
                    <svg class="w-10 h-10 mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p class="text-[11px] uppercase font-bold tracking-widest text-center text-slate-500">Sin datos de logística<br>en este periodo</p>
                </div>
            @else
                <div class="flex flex-col gap-2">
                    @foreach($operators as $op)
                        <div class="group flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg border border-slate-100 dark:border-white/5 bg-white dark:bg-white/5 hover:border-indigo-200 dark:hover:border-indigo-500/30 transition-all duration-200">
                            {{-- Avatar --}}
                            <div class="relative flex-shrink-0">
                                <img src="{{ $op['avatar'] }}" alt="{{ $op['name'] }}" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover ring-2 ring-white dark:ring-gray-900 bg-slate-50">
                                <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-gray-900 {{ $op['status'] === 'En Ruta' ? 'bg-amber-500' : 'bg-emerald-500' }}"></div>
                            </div>
                            
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 truncate">{{ $op['name'] }}</h4>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] sm:text-[9px] font-bold uppercase tracking-wide {{ $op['status'] === 'En Ruta' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' }}">
                                    {{ $op['status'] }}
                                </span>
                            </div>

                            {{-- Stats --}}
                            <div class="flex-shrink-0 text-right">
                                <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400">
                                    {{ $op['dispatches'] }}
                                    <span class="text-[8px] sm:text-[9px] font-bold text-slate-400 uppercase">viajes</span>
                                </div>
                                <div class="w-12 sm:w-16 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mt-1">
                                    <div class="h-full bg-gradient-to-r from-indigo-400 to-indigo-600 rounded-full" style="width: {{ $op['performance'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        
        @if(!empty($operators))
            <div class="px-3 sm:px-5 py-2 sm:py-3 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 flex items-center justify-between rounded-b-xl">
                <span class="text-[9px] sm:text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ count($operators) }} pilotos</span>
                <a href="{{ route('filament.admin.resources.dispatches.index') }}" class="text-[10px] sm:text-[11px] font-black text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 uppercase tracking-wider flex items-center gap-1 transition-colors">
                    Ver Despachos
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
