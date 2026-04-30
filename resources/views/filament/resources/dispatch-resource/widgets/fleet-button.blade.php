<x-filament-widgets::widget>
    <div class="flex justify-end mb-4">
        <x-filament::button
            href="{{ \App\Filament\Resources\TruckResource::getUrl('index') }}"
            tag="a"
            icon="heroicon-m-truck"
            color="gray"
            class="shadow-sm"
        >
            Administrar Flota de Camiones
        </x-filament::button>
    </div>
</x-filament-widgets::widget>
