<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Bitácora';
    protected static ?string $modelLabel = 'Evento';
    protected static ?string $pluralModelLabel = 'Bitácora';
    protected static ?int $navigationSort = 2;

    // ✅ Solo usuarios con permiso audit.view ven bitácora
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(['default' => 1, 'md' => 12])
                ->schema([
                    Forms\Components\Section::make('Resumen de Actividad')
                        ->icon('heroicon-o-chat-bubble-bottom-center-text')
                        ->schema([
                            Forms\Components\Placeholder::make('summary_html')
                                ->label('')
                                ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                    '<div class="prose dark:prose-invert max-w-none">' . 
                                    static::humanChangesSummary($record) . 
                                    '</div>'
                                )),
                            
                            Forms\Components\Textarea::make('description')
                                ->label('Contexto / Nota')
                                ->placeholder('Sin descripción adicional')
                                ->disabled()
                                ->rows(2)
                                ->visible(fn ($record) => !empty($record->description)),
                        ])
                        ->columnSpan(['default' => 1, 'md' => 8]),

                    Forms\Components\Section::make('Información del Evento')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\TextInput::make('event')
                                ->label('Acción')
                                ->disabled()
                                ->formatStateUsing(fn ($state) => static::humanEvent($state))
                                ->prefixIcon('heroicon-o-bolt'),

                            Forms\Components\TextInput::make('module')
                                ->label('Módulo')
                                ->disabled()
                                ->formatStateUsing(fn ($state) => static::humanModule($state))
                                ->prefixIcon('heroicon-o-cube'),

                            Forms\Components\TextInput::make('user_name')
                                ->label('Usuario Responsable')
                                ->disabled()
                                ->default(fn ($record) => $record->user?->name ?? 'Sistema / Automatizado')
                                ->prefixIcon('heroicon-o-user'),

                            Forms\Components\TextInput::make('created_at')
                                ->label('Fecha y Hora')
                                ->disabled()
                                ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s'))
                                ->prefixIcon('heroicon-o-calendar'),
                        ])
                        ->columnSpan(['default' => 1, 'md' => 4]),
                ]),

            Forms\Components\Section::make('Detalles Técnicos (Estructura JSON)')
                ->description('Esta sección contiene la información técnica para auditoría profunda.')
                ->schema([
                    Forms\Components\Tabs::make('json_tabs')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Antes')
                                ->icon('heroicon-o-arrow-path')
                                ->schema([
                                    Forms\Components\Textarea::make('old_values')
                                        ->label('')
                                        ->disabled()
                                        ->rows(10)
                                        ->formatStateUsing(fn ($state) => static::prettyJson($state)),
                                ]),
                            Forms\Components\Tabs\Tab::make('Después')
                                ->icon('heroicon-o-arrow-right-circle')
                                ->schema([
                                    Forms\Components\Textarea::make('new_values')
                                        ->label('')
                                        ->disabled()
                                        ->rows(10)
                                        ->formatStateUsing(fn ($state) => static::prettyJson($state)),
                                ]),
                            Forms\Components\Tabs\Tab::make('Metadatos')
                                ->icon('heroicon-o-globe-alt')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('ip_address')
                                                ->label('Dirección IP')
                                                ->disabled(),
                                            Forms\Components\TextInput::make('method')
                                                ->label('Método HTTP')
                                                ->disabled(),
                                            Forms\Components\TextInput::make('url')
                                                ->label('URL Solicitada')
                                                ->columnSpanFull()
                                                ->disabled(),
                                            Forms\Components\TextInput::make('user_agent')
                                                ->label('Navegador / Agente')
                                                ->columnSpanFull()
                                                ->disabled(),
                                        ]),
                                ]),
                        ]),
                ])
                ->collapsible()
                ->collapsed()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s', config('app.timezone'))
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->default('Sistema / Automatizado')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('module')
                    ->label('Módulo')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'inventory' => 'info',
                        'sales' => 'success',
                        'products' => 'warning',
                        'production' => 'primary',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn ($state) => static::humanModule($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Acción')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'deleted' => 'danger',
                        'created' => 'success',
                        'updated' => 'info',
                        'login' => 'primary',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn ($state) => static::humanEvent($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Registro (tipo)')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => static::humanModel($state))
                    ->limit(40),

                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('Registro (ID)')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('Módulo')
                    ->options([
                        'auth' => 'Acceso / Autenticación',
                        'users' => 'Usuarios',
                        'products' => 'Productos',
                        'inventory' => 'Inventario',
                        'sales' => 'Ventas',
                        'orders' => 'Pedidos',
                        'production' => 'Producción',
                        'dispatch' => 'Despachos',
                    ]),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Acción')
                    ->options([
                        'created' => 'Creación',
                        'updated' => 'Actualización',
                        'deleted' => 'Eliminación',
                        'login' => 'Inicio de sesión',
                    ]),
            ])
            ->deferLoading()
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver Detalle')->icon('heroicon-o-eye')->color('gray'),
            ])
            ->simplePagination()
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view'  => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    // =========================
    // Helpers (todo en 1 archivo)
    // =========================

    protected static function humanModule(?string $module): string
    {
        $module = (string) $module;

        $map = [
            'auth' => 'Acceso / Autenticación',
            'users' => 'Usuarios',
            'roles' => 'Roles y permisos',
            'products' => 'Productos',
            'catalogs' => 'Catálogos',
            'inventory' => 'Inventario / Kardex',
            'purchases' => 'Compras',
            'sales' => 'Ventas',
            'orders' => 'Pedidos',
            'production' => 'Producción',
            'dispatch' => 'Despachos / Camiones',
            'reports' => 'Reportes',
            'audit' => 'Bitácora',
        ];

        return $map[$module] ?? ucfirst(str_replace('_', ' ', $module));
    }

    protected static function humanEvent(?string $event): string
    {
        $event = (string) $event;

        $map = [
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            'restored' => 'Restauración',
            'roles_updated' => 'Cambio de roles',
            'status_updated' => 'Cambio de estado (activo/inactivo)',
        ];

        return $map[$event] ?? ucfirst(str_replace('_', ' ', $event));
    }

    protected static function humanModel(?string $model): string
    {
        $model = (string) $model;

        // Ej: App\Models\User -> Usuario
        $map = [
            'App\Models\User' => 'Usuario',
            'App\Models\Product' => 'Producto',
        ];

        return $map[$model] ?? class_basename($model);
    }

    protected static function prettyJson($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
            }
            return $value;
        }

        return (string) $value;
    }

    protected static function humanLabelForKey(string $key): string
    {
        $map = [
            // General
            'id' => 'ID de Registro',
            'name' => 'Nombre',
            'status' => 'Estado',
            'is_active' => '¿Está Activo?',
            'description' => 'Descripción',
            'notes' => 'Notas/Observaciones',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Última Modificación',
            'type' => 'Tipo',
            'created_by' => 'Usuario Creador',

            // Usuarios / Roles
            'email' => 'Correo Electrónico',
            'password' => 'Contraseña',

            // Productos
            'sku' => 'Código (SKU)',
            'sale_price' => 'Precio de Venta',
            'cost_price' => 'Costo de Producción',
            'purchase_cost' => 'Costo de Compra',
            'unit_of_measure_id' => 'Unidad de Medida',
            'color_id' => 'Color Seleccionado',

            // Ventas
            'sale_number' => 'Número de Venta',
            'customer_name' => 'Cliente',
            'total' => 'Monto Total',
            'discount_amount' => 'Descuento Aplicado',
            'from_warehouse_id' => 'Bodega de Origen',
            'from_truck_id' => 'Camión de Origen',

            // Pedidos
            'order_number' => 'Número de Pedido',
            'delivery_address' => 'Dirección de Entrega',
            'customer_nit' => 'NIT del Cliente',
            'payment_method' => 'Forma de Pago',

            // Despachos
            'dispatch_number' => 'Número de Despacho',
            'truck_id' => 'Vehículo/Camión',
            'driver_id' => 'Piloto Responsable',
            'driver_name' => 'Piloto (Texto)',
            'route' => 'Ruta Designada',

            // Inventario
            'movement_number' => 'Nro. Movimiento',
            'movement_type' => 'Tipo de Movimiento',
            'warehouse_id' => 'Bodega Relacionada',
            'quantity' => 'Cantidad Operada',
            'unit_price' => 'Precio Unitario',

            // Producción
            'production_number' => 'Orden de Producción',
            'start_date' => 'Fecha de Inicio',
            'end_date' => 'Fecha de Fin',
        ];

        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    protected static function humanValue(string $key, $value): string
    {
        if ($value === null) return 'Vacío / Sin asignar';
        if ($value === true || $value === 1 || $value === '1') return 'SÍ / Activo';
        if ($value === false || $value === 0 || $value === '0') return 'NO / Inactivo';

        // Intentar resolver IDs a nombres
        try {
            if (str_ends_with($key, '_id')) {
                $modelClass = match ($key) {
                    'user_id', 'created_by', 'driver_id' => \App\Models\User::class,
                    'product_id' => \App\Models\Product::class,
                    'warehouse_id', 'from_warehouse_id' => \App\Models\Warehouse::class,
                    'truck_id', 'from_truck_id' => \App\Models\Truck::class,
                    'unit_of_measure_id' => \App\Models\UnitOfMeasure::class,
                    'color_id' => \App\Models\Color::class,
                    'dispatch_id' => \App\Models\Dispatch::class,
                    default => null
                };

                if ($modelClass) {
                    $record = $modelClass::find($value);
                    if ($record) {
                        return ($record->name ?? $record->number ?? $record->dispatch_number ?? $record->order_number ?? $value) . " (ID: {$value})";
                    }
                }
            }
        } catch (\Exception $e) {
            // Silencio
        }

        // Formatear estados conocidos
        if ($key === 'status') {
            $statusMap = [
                'pending' => 'Pendiente',
                'confirmed' => 'Confirmado',
                'completed' => 'Completado',
                'delivered' => 'Entregado',
                'cancelled' => 'Cancelado',
                'in_progress' => 'En Proceso',
                'draft' => 'Borrador',
            ];
            return $statusMap[$value] ?? $value;
        }

        return (string) $value;
    }

    protected static function humanChangesSummary($record): string
    {
        $old = $record->old_values;
        $new = $record->new_values;

        if (is_string($old)) $old = json_decode($old, true);
        if (is_string($new)) $new = json_decode($new, true);

        $old = is_array($old) ? $old : [];
        $new = is_array($new) ? $new : [];

        if ($record->event === 'created') {
            $data = collect($new)->take(12)->map(function($v, $k) {
                return "<div class='flex items-center gap-2 mb-1'><span class='text-gray-400'>•</span> <b>" . static::humanLabelForKey($k) . ":</b> " . static::humanValue($k, $v) . "</div>";
            })->implode("");
            
            return "<div class='bg-green-50 dark:bg-green-950/20 border-l-4 border-green-500 p-4 rounded-r-lg'>
                        <div class='flex items-center gap-2 text-green-700 dark:text-green-400 font-black mb-2'>
                            <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>
                            REGISTRO CREADO
                        </div>
                        <div class='text-sm opacity-90'>Datos principales ingresados:</div>
                        <div class='mt-3 text-sm'>{$data}</div>
                    </div>";
        }

        if ($record->event === 'deleted') {
            $data = collect($old)->take(8)->map(function($v, $k) {
                return "<div class='flex items-center gap-2 mb-1'><span class='text-gray-400'>•</span> <b>" . static::humanLabelForKey($k) . ":</b> " . static::humanValue($k, $v) . "</div>";
            })->implode("");

            return "<div class='bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 p-4 rounded-r-lg'>
                        <div class='flex items-center gap-2 text-red-700 dark:text-red-400 font-black mb-2'>
                            <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path></svg>
                            REGISTRO ELIMINADO
                        </div>
                        <div class='text-sm opacity-90'>Contenido del registro antes de borrar:</div>
                        <div class='mt-3 text-sm'>{$data}</div>
                    </div>";
        }

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $lines = [];

        foreach ($keys as $key) {
            if (in_array($key, ['remember_token', 'updated_at', 'created_at'], true)) continue;

            $before = $old[$key] ?? null;
            $after  = $new[$key] ?? null;

            if ($before == $after) continue;

            $label = static::humanLabelForKey($key);
            $beforeText = static::humanValue($key, $before);
            $afterText  = static::humanValue($key, $after);

            if ($key === 'password') {
                $beforeText = '********';
                $afterText  = '********';
            }

            $lines[] = "<div class='py-2 border-b border-gray-100 dark:border-gray-800 last:border-0'>
                            <div class='text-xs font-bold text-gray-500 uppercase tracking-wider mb-1'>{$label}</div>
                            <div class='flex items-center gap-3 flex-wrap'>
                                <span class='px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded text-sm line-through decoration-red-400/50'>{$beforeText}</span>
                                <svg class='w-4 h-4 text-gray-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M14 5l7 7m0 0l-7 7m7-7H3'></path></svg>
                                <span class='px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-sm font-bold'>{$afterText}</span>
                            </div>
                        </div>";
        }

        if (empty($lines)) {
            return "<div class='text-gray-500 italic p-4 text-center'>Actualización técnica realizada sin cambios en campos visibles.</div>";
        }

        $allChanges = implode("", $lines);

        return "<div class='bg-blue-50 dark:bg-blue-950/20 border-l-4 border-blue-500 p-4 rounded-r-lg mb-4'>
                    <div class='flex items-center gap-2 text-blue-700 dark:text-blue-400 font-bold'>
                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'></path></svg>
                        CAMBIOS DETECTADOS
                    </div>
                </div>
                <div class='space-y-1'>
                    {$allChanges}
                </div>";
    }
}
