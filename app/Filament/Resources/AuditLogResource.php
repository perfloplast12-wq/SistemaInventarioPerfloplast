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
        return auth()->user()?->can('audit.view') ?? false;
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
            Forms\Components\Split::make([
                Forms\Components\Section::make('Resumen de Actividad')
                    ->schema([
                        Forms\Components\Placeholder::make('summary_html')
                            ->label('¿Qué cambió?')
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                '<div class="prose dark:prose-invert max-w-none">' . 
                                nl2br(static::humanChangesSummary($record)) . 
                                '</div>'
                            )),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Contexto')
                            ->disabled()
                            ->rows(2),
                    ])->grow(),

                Forms\Components\Section::make('Información del Evento')
                    ->schema([
                        Forms\Components\TextInput::make('event')
                            ->label('Acción')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => static::humanEvent($state)),

                        Forms\Components\TextInput::make('module')
                            ->label('Módulo')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => static::humanModule($state)),

                        Forms\Components\TextInput::make('user.name')
                            ->label('Usuario Responsable')
                            ->disabled(),

                        Forms\Components\TextInput::make('created_at')
                            ->label('Fecha y Hora')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s')),
                    ])->columnSpan(1),
            ])->columnSpanFull(),

            Forms\Components\Section::make('Detalles Técnicos (Estructura JSON)')
                ->description('Esta sección contiene la información técnica para auditoría profunda.')
                ->schema([
                    Forms\Components\Tabs::make('json_tabs')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Antes')
                                ->schema([
                                    Forms\Components\Textarea::make('old_values')
                                        ->label('')
                                        ->disabled()
                                        ->rows(10)
                                        ->formatStateUsing(fn ($state) => static::prettyJson($state)),
                                ]),
                            Forms\Components\Tabs\Tab::make('Después')
                                ->schema([
                                    Forms\Components\Textarea::make('new_values')
                                        ->label('')
                                        ->disabled()
                                        ->rows(10)
                                        ->formatStateUsing(fn ($state) => static::prettyJson($state)),
                                ]),
                            Forms\Components\Tabs\Tab::make('Metadatos')
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
                    // ✅ respeta tu zona horaria configurada en APP_TIMEZONE
                    ->dateTime('d/m/Y H:i:s', config('app.timezone'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('module')
                    ->label('Módulo')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => static::humanModule($state)),

                Tables\Columns\TextColumn::make('event')
                    ->label('Acción')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => static::humanEvent($state)),

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
                    ->options(fn () => AuditLog::query()
                        ->select('module')
                        ->distinct()
                        ->pluck('module', 'module')
                        ->map(fn ($m) => static::humanModule($m))
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Acción')
                    ->options(fn () => AuditLog::query()
                        ->select('event')
                        ->distinct()
                        ->pluck('event', 'event')
                        ->map(fn ($e) => static::humanEvent($e))
                        ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
            ])
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
            return "✨ **Registro creado.** Estos son algunos de los datos ingresados:\n" . 
                   collect($new)->take(8)->map(fn($v, $k) => "- **" . static::humanLabelForKey($k) . "**: " . static::humanValue($k, $v))->implode("\n");
        }

        if ($record->event === 'deleted') {
            return "🗑️ **Registro eliminado.** Se borró el registro que contenía:\n" . 
                   collect($old)->take(5)->map(fn($v, $k) => "- **" . static::humanLabelForKey($k) . "**: " . static::humanValue($k, $v))->implode("\n");
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

            $lines[] = "• **{$label}**: de \"{$beforeText}\" a \"{$afterText}\"";
        }

        if (empty($lines)) {
            return "Se realizó una actualización pero no se detectaron cambios en campos principales.";
        }

        return "🔄 **Se actualizaron los siguientes campos:**\n\n" . implode("\n", $lines);
    }
}
