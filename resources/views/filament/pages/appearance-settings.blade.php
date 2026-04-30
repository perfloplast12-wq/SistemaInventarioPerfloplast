<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-4">
            <x-filament::button 
                tag="a" 
                href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}" 
                color="gray" 
                outlined
            >
                Volver
            </x-filament::button>

            <x-filament::button type="submit" size="lg">
                Guardar Cambios
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
