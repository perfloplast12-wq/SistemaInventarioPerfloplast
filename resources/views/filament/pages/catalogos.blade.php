<style>
    .cat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
    }
    .dark .cat-card {
        background: #111827;
        border: 1px solid rgba(255,255,255,0.06);
    }
    .cat-card-footer {
        background: #f9fafb;
        border-top: 1px solid #f3f4f6;
    }
    .dark .cat-card-footer {
        background: rgba(255,255,255,0.03);
        border-top: 1px solid rgba(255,255,255,0.06);
    }
    .cat-header {
        background: #ffffff;
        border: 1px solid #e5e7eb;
    }
    .dark .cat-header {
        background: #111827;
        border: 1px solid rgba(255,255,255,0.06);
    }
</style>

<div class="space-y-4">
    <div class="cat-header flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl shadow-sm">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white leading-none">Maestros</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase font-black tracking-widest">Maestros del Sistema</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        @php
            $catalogos = [
                ['label' => 'Unidades', 'color' => 'emerald', 'icon' => 'scale', 'url' => \App\Filament\Resources\UnitOfMeasureResource::getUrl('index'), 'desc' => 'U. Medida'],
                ['label' => 'Colores', 'color' => 'rose', 'icon' => 'swatch', 'url' => \App\Filament\Resources\ColorResource::getUrl('index'), 'desc' => 'Variantes'],
                ['label' => 'Bodegas', 'color' => 'blue', 'icon' => 'home-modern', 'url' => \App\Filament\Resources\WarehouseResource::getUrl('index'), 'desc' => 'Almacenes'],
                ['label' => 'Camiones', 'color' => 'amber', 'icon' => 'truck', 'url' => \App\Filament\Resources\TruckResource::getUrl('index'), 'desc' => 'Flota'],
                ['label' => 'Turnos', 'color' => 'slate', 'icon' => 'clock', 'url' => \App\Filament\Resources\ShiftResource::getUrl('index'), 'desc' => 'Horarios'],
            ];
        @endphp

        @foreach($catalogos as $cat)
            <div class="cat-card rounded-lg shadow-sm overflow-hidden flex flex-col hover:border-{{ $cat['color'] }}-500 transition-all group">
                <div class="p-3 flex-1 flex flex-col items-center text-center">
                    <div class="p-2 bg-{{ $cat['color'] }}-50 dark:bg-{{ $cat['color'] }}-500/10 text-{{ $cat['color'] }}-600 dark:text-{{ $cat['color'] }}-400 rounded-lg mb-2 group-hover:scale-110 transition-transform">
                        <x-dynamic-component :component="'heroicon-o-' . $cat['icon']" class="w-6 h-6" />
                    </div>
                    <h3 class="text-xs font-black text-gray-950 dark:text-white uppercase tracking-tighter leading-none">{{ $cat['label'] }}</h3>
                    <p class="text-[9px] text-gray-500 dark:text-gray-400 mt-1 uppercase font-bold tracking-widest">{{ $cat['desc'] }}</p>
                </div>
                <div class="cat-card-footer px-2 py-2">
                    <x-filament::button 
                        tag="a" 
                        :href="$cat['url']" 
                        color="gray" 
                        size="xs"
                        outlined
                        class="w-full font-black uppercase tracking-widest text-[9px]"
                    >
                        Gestionar
                    </x-filament::button>
                </div>
            </div>
        @endforeach
    </div>
</div>
