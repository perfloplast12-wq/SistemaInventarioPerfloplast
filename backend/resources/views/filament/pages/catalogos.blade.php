<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header (solo este, porque ya quitamos el heading de Filament) --}}
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl
                        bg-white ring-1 ring-gray-200
                        dark:bg-white/5 dark:ring-white/10">
                <x-heroicon-o-squares-2x2 class="h-7 w-7 text-gray-900 dark:text-white" />
            </div>

            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Catálogos</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Configuración base del sistema (bodegas, camiones, unidades, turnos, etc.)
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

            {{-- Unidades de medida --}}
            <x-filament::section>
                <div class="flex items-start gap-4">
                    <x-filament::icon icon="heroicon-o-scale" class="h-7 w-7" />

                    <div class="space-y-2">
                        <div>
                            <h2 class="text-lg font-semibold">Unidades de medida</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Unidad, paquete, kg, saco y otros.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <x-filament::button tag="a" :href="$this->getUnitsUrl()" color="primary">
                                Abrir
                            </x-filament::button>

                            <x-filament::button tag="a" :href="$this->getUnitsCreateUrl()" color="gray">
                                Crear
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- Turnos --}}
            <x-filament::section>
                <div class="flex items-start gap-4">
                    <x-filament::icon icon="heroicon-o-clock" class="h-7 w-7" />

                    <div class="space-y-2">
                        <div>
                            <h2 class="text-lg font-semibold">Turnos</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Configura mañana, tarde, noche (y los que necesites).
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <x-filament::button tag="a" :href="$this->getShiftsUrl()" color="primary">
                                Abrir
                            </x-filament::button>

                            <x-filament::button tag="a" :href="$this->getShiftsCreateUrl()" color="gray">
                                Crear
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- Bodegas --}}
            <x-filament::section>
                <div class="flex items-start gap-4">
                    <x-filament::icon icon="heroicon-o-building-storefront" class="h-7 w-7" />

                    <div class="space-y-2">
                        <div>
                            <h2 class="text-lg font-semibold">Bodegas</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Bodegas, mostradores y bodegas móviles.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <x-filament::button tag="a" :href="$this->getWarehousesUrl()" color="primary">
                                Abrir
                            </x-filament::button>

                            <x-filament::button tag="a" :href="$this->getWarehousesCreateUrl()" color="gray">
                                Crear
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- Camiones --}}
            <x-filament::section>
                <div class="flex items-start gap-4">
                    <x-filament::icon icon="heroicon-o-truck" class="h-7 w-7" />

                    <div class="space-y-2">
                        <div>
                            <h2 class="text-lg font-semibold">Camiones</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Registro y control de camiones (rutas y transferencias).
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <x-filament::button tag="a" :href="$this->getTrucksUrl()" color="primary">
                                Abrir
                            </x-filament::button>

                            <x-filament::button tag="a" :href="$this->getTrucksCreateUrl()" color="gray">
                                Crear
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>

        </div>
    </div>
</x-filament-panels::page>
