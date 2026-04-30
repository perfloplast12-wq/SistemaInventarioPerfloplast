<x-filament-widgets::widget>
    <div class="flex justify-end mb-4">
        <x-filament::button
            href="{{ \App\Filament\Resources\ShiftResource::getUrl('index') }}"
            tag="a"
            icon="heroicon-m-clock"
            color="gray"
            class="shadow-sm"
        >
            Administrar Horarios y Turnos
        </x-filament::button>
    </div>
</x-filament-widgets::widget>
