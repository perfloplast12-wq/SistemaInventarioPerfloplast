<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl
                        bg-white ring-1 ring-gray-200
                        dark:bg-white/5 dark:ring-white/10">
                <x-heroicon-o-cube class="h-7 w-7 text-gray-900 dark:text-white" />
            </div>

            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Inventario</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Gestión completa de productos y stock</p>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

            {{-- Total productos --}}
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm
                    transition hover:shadow-md
                    dark:border-white/10 dark:bg-white/5"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Total productos</p>
                        <p class="mt-2 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                            {{ $this->getTotalProductsCount() }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Materia prima + Producto terminado
                        </p>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl
                            border border-gray-200 bg-gray-50
                            dark:border-white/10 dark:bg-white/5"
                    >
                        <x-heroicon-o-squares-2x2 class="h-6 w-6 text-gray-900 dark:text-white" />
                    </div>
                </div>
            </div>

            {{-- Materia prima --}}
            <div
                class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm
                    transition hover:shadow-md
                    dark:border-emerald-400/20 dark:bg-white/5"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Materia prima</p>
                        <p class="mt-2 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                            {{ $this->getRawMaterialsCount() }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Registrados como materia prima
                        </p>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl
                            border border-emerald-200 bg-emerald-50
                            dark:border-emerald-400/20 dark:bg-emerald-500/15"
                    >
                        <x-heroicon-o-beaker class="h-6 w-6 text-emerald-700 dark:text-emerald-200" />
                    </div>
                </div>
            </div>

            {{-- Producto terminado --}}
            <div
                class="rounded-2xl border border-blue-200 bg-white p-6 shadow-sm
                    transition hover:shadow-md
                    dark:border-blue-400/20 dark:bg-white/5"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Producto terminado</p>
                        <p class="mt-2 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                            {{ $this->getFinishedProductsCount() }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Registrados como producto terminado
                        </p>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl
                            border border-blue-200 bg-blue-50
                            dark:border-blue-400/20 dark:bg-blue-500/15"
                    >
                        <x-heroicon-o-archive-box class="h-6 w-6 text-blue-700 dark:text-blue-200" />
                    </div>
                </div>
            </div>

        </div>


        {{-- Accesos --}}
        <x-filament::section>
            <div class="space-y-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Accesos</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Secciones principales del inventario</p>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">

                {{-- Materia prima --}}
                <x-filament::section>
                    <div class="flex items-start gap-4">
                        <x-filament::icon icon="heroicon-o-beaker" class="h-7 w-7" />
                        <div class="space-y-2">
                            <div>
                                <h2 class="text-lg font-semibold">Materia prima</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Gestionar materias primas
                                </p>
                            </div>

                            <x-filament::button
                                tag="a"
                                :href="$this->getRawMaterialsIndexUrl()"
                                icon="heroicon-m-arrow-top-right-on-square"
                                color="warning"
                            >
                                Abrir
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Producto terminado --}}
                <x-filament::section>
                    <div class="flex items-start gap-4">
                        <x-filament::icon icon="heroicon-o-archive-box" class="h-7 w-7" />
                        <div class="space-y-2">
                            <div>
                                <h2 class="text-lg font-semibold">Producto terminado</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Gestionar productos terminados
                                </p>
                            </div>

                            <x-filament::button
                                tag="a"
                                :href="$this->getFinishedProductsIndexUrl()"
                                icon="heroicon-m-arrow-top-right-on-square"
                                color="warning"
                            >
                                Abrir
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Kardex --}}
                <x-filament::section>
                    <div class="flex items-start gap-4">
                        <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-7 w-7" />
                        <div class="space-y-2">
                            <div>
                                <h2 class="text-lg font-semibold">Kardex</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Historial de movimientos
                                </p>
                            </div>

                            <x-filament::button
                                tag="a"
                                :href="$this->getKardexUrl()"
                                icon="heroicon-m-arrow-top-right-on-square"
                                color="warning"
                            >
                                Abrir
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

            </div>
        </x-filament::section>

        {{-- Movimientos --}}
        <x-filament::section>
            <div class="space-y-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Movimientos</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Entradas, salidas, ajustes y transferencias</p>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">

                {{-- Entrada --}}
                <x-filament::section>
                    <div class="flex items-start gap-4">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-7 w-7" />
                        <div class="space-y-2">
                            <div>
                                <h2 class="text-lg font-semibold">Entrada</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Compra / ingreso</p>
                            </div>

                            <x-filament::button
                                tag="a"
                                :href="$this->getMovementsUrl('in')"
                                icon="heroicon-m-arrow-top-right-on-square"
                                color="warning"
                            >
                                Abrir
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Salida --}}
                <x-filament::section>
                    <div class="flex items-start gap-4">
                        <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-7 w-7" />
                        <div class="space-y-2">
                            <div>
                                <h2 class="text-lg font-semibold">Salida</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Venta / consumo</p>
                            </div>

                            <x-filament::button
                                tag="a"
                                :href="$this->getMovementsUrl('out')"
                                icon="heroicon-m-arrow-top-right-on-square"
                                color="warning"
                            >
                                Abrir
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Ajuste --}}
                <x-filament::section>
                    <div class="flex items-start gap-4">
                        <x-filament::icon icon="heroicon-o-pencil-square" class="h-7 w-7" />
                        <div class="space-y-2">
                            <div>
                                <h2 class="text-lg font-semibold">Ajuste</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Corrección</p>
                            </div>

                            <x-filament::button
                                tag="a"
                                :href="$this->getMovementsUrl('adjust')"
                                icon="heroicon-m-arrow-top-right-on-square"
                                color="warning"
                            >
                                Abrir
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Transferencia --}}
                <x-filament::section>
                    <div class="flex items-start gap-4">
                        <x-filament::icon icon="heroicon-o-truck" class="h-7 w-7" />
                        <div class="space-y-2">
                            <div>
                                <h2 class="text-lg font-semibold">Transferencia</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Bodega ↔ Camión</p>
                            </div>

                            <x-filament::button
                                tag="a"
                                :href="$this->getMovementsUrl('transfer')"
                                icon="heroicon-m-arrow-top-right-on-square"
                                color="warning"
                            >
                                Abrir
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
