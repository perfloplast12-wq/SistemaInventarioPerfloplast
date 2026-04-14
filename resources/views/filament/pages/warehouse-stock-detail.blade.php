<div class="space-y-4">
    {{-- Back button --}}
    <div class="flex items-center gap-3">
        <a href="{{ \App\Filament\Pages\Inventario::getUrl() }}" 
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
            <x-heroicon-m-arrow-left class="w-4 h-4" />
            Volver a Inventario
        </a>
    </div>

    {{-- Table --}}
    <div>
        {{ $this->table }}
    </div>
</div>
