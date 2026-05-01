<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Truck;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Movimientos';
    protected static ?string $modelLabel = 'Movimiento';
    protected static ?string $pluralModelLabel = 'Movimientos';

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('inventory_movements.view') ?? false) && !auth()->user()?->hasRole('sales');
    }

    protected static ?string $navigationGroup = 'Producción e Inventario';
    protected static ?int $navigationSort = 3;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        $type = request()->query('type');

        return $form->schema([
            Forms\Components\Section::make('Producto y Cantidad')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Producto')
                                ->options(\App\Models\Product::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Get $get) {
                                    if (!$state) return;
                                    $product = \App\Models\Product::find($state);
                                    if ($product) {
                                        $set('product_category', $product->type);
                                        // Auto-Select movement type if empty
                                        if (!$get('type')) {
                                            if ($product->type === 'raw_material') $set('type', 'in');
                                            if ($product->type === 'finished_product') $set('type', 'transfer');
                                        }
                                    }
                                })
                                ->columnSpan(1),

                            Forms\Components\Select::make('color_id')
                                ->label('Color/Variante')
                                ->options(function(Get $get) {
                                    $productId = $get('product_id');
                                    if (!$productId) return [];

                                    return \App\Models\Stock::query()
                                        ->where('product_id', $productId)
                                        ->where('quantity', '>', 0)
                                        ->with('color')
                                        ->get()
                                        ->mapWithKeys(fn($s) => [
                                            ($s->color_id) => $s->color ? $s->color->display_name : '(Sin Variante)'
                                        ])
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->visible(fn (Get $get) => $get('product_category') === 'finished_product' || \App\Models\Product::find($get('product_id'))?->type === 'finished_product')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad a Operar')
                                ->numeric()
                                ->extraInputAttributes(['step' => '0.01'])
                                ->required()
                                ->minValue(0.01)
                                ->live()
                                ->rules([
                                    fn (Get $get, $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $type = $get('type');
                                        // Solo validar en salidas o traslados
                                        if (!in_array($type, ['out', 'transfer', 'return'])) return;
                                        if ($type === 'transfer' && $get('motive') === 'unload') return; // Descarga no resta de bodega (resta de camion, validar luego)

                                        $qty = (float) $value;
                                        $productId = $get('product_id');
                                        $colorId = $get('color_id');
                                        
                                        // Determinar origen segun el tipo
                                        $fromWh = $get('from_warehouse_id');
                                        $fromTr = $get('from_truck_id');
                                        
                                        if (!$productId) return;
                                        if (!$fromWh && !$fromTr) return;

                                        $stockQuery = \App\Models\Stock::query()
                                            ->where('product_id', $productId)
                                            ->where('color_id', $colorId)
                                            ->where('warehouse_id', $fromWh)
                                            ->where('truck_id', $fromTr);

                                        $available = (float) ($stockQuery->value('quantity') ?? 0);

                                        // Si estamos editando, sumar la cantidad actual del registro al disponible
                                        if ($record && $record->product_id == $productId && $record->color_id == $colorId) {
                                            // Solo si el origen coincide
                                            $oldFromWh = $record->from_warehouse_id;
                                            $oldFromTr = $record->from_truck_id;
                                            if ($oldFromWh == $fromWh && $oldFromTr == $fromTr) {
                                                $available += abs((float)$record->quantity);
                                            }
                                        }

                                        if ($available < $qty) {
                                            $fail("Stock insuficiente en la ubicación de origen. Disponible: " . number_format($available, 2));
                                        }
                                    },
                                ])
                                ->dehydrateStateUsing(function ($state, Get $get) {
                                    $qty = (float) $state;
                                    if ($get('type') === 'adjust' && $get('adjustment_direction') === 'subtract') {
                                        return -$qty;
                                    }
                                    return $qty;
                                })
                                ->columnSpan(1),
                            
                            Forms\Components\Placeholder::make('stock_available_hint')
                                ->label('Existencia en Origen')
                                ->content(function (Get $get, $record) {
                                    $productId = $get('product_id');
                                    $colorId = $get('color_id');
                                    $fromWh = $get('from_warehouse_id');
                                    $fromTr = $get('from_truck_id');

                                    if (!$productId || (!$fromWh && !$fromTr)) return 'Seleccione producto y origen...';

                                    $stock = (float) (\App\Models\Stock::query()
                                        ->where('product_id', $productId)
                                        ->where('color_id', $colorId)
                                        ->where('warehouse_id', $fromWh)
                                        ->where('truck_id', $fromTr)
                                        ->value('quantity') ?? 0);
                                    
                                    // Compensar si es edicion
                                    if ($record && $record->product_id == $productId && $record->color_id == $colorId) {
                                        if ($record->from_warehouse_id == $fromWh && $record->from_truck_id == $fromTr) {
                                            $stock += abs((float)$record->quantity);
                                        }
                                    }

                                    $colorName = $colorId ? (\App\Models\Color::find($colorId)?->name ?? 'N/A') : 'Base';
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='flex items-center space-x-2'>
                                            <span class='text-2xl font-bold " . ($stock > 0 ? 'text-success-600' : 'text-danger-600') . "'>" . number_format($stock, 2) . "</span>
                                            <span class='text-xs text-gray-500'>unidades disponibles de <b>{$colorName}</b></span>
                                        </div>
                                    ");
                                })
                                ->columnSpan(2),

                            Forms\Components\Hidden::make('product_category'),
                        ]),
                ]),

            Forms\Components\Section::make('Datos de la Operación')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo de movimiento')
                        ->options([
                            'in'       => 'Entrada (Ingreso por Compra)',
                            'out'      => 'Salida (Consumo o Descarte)',
                            'transfer' => 'Traslado (Mover entre Bodega/Camión)',
                            'return'   => 'Retorno (Devolución de Ruta)',
                            'adjust'   => 'Ajuste (Corrección de Inventario)',
                        ])
                        ->default($type)
                        ->disabled(fn() => !empty($type))
                        ->dehydrated()
                        ->required()
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\Placeholder::make('out_warning')
                        ->label('')
                        ->visible(fn(Get $get) => $get('type') === 'out')
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="p-2 bg-amber-50 border border-amber-200 text-amber-700 text-xs rounded">
                                ⚠️ <b>Atención:</b> Si esto es una venta a cliente, se recomienda usar el módulo de <b>Ventas</b> para generar factura.
                            </div>
                        ')),

                    Forms\Components\Select::make('motive')
                        ->label('Motivo de Transferencia')
                        ->options([
                            'load' => 'Carga de Camión (Bodega -> Camión)',
                            'unload' => 'Descarga de Camión (Camión -> Bodega)',
                            'internal_wh' => 'Entre Bodegas (Bodega -> Bodega)',
                            'transshipment' => 'Trasbordo (Camión -> Camión)',
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'transfer')
                        ->required(fn (Get $get) => $get('type') === 'transfer')
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\Select::make('adjustment_direction')
                        ->label('¿Qué desea hacer?')
                        ->options([
                            'add'      => 'Sumar (Hay MÁS en físico que en sistema)',
                            'subtract' => 'Restar (Hay MENOS en físico que en sistema)',
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'adjust')
                        ->required(fn (Get $get) => $get('type') === 'adjust')
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\ToggleButtons::make('adjustment_location_type')
                        ->label('¿Dónde se hará el ajuste?')
                        ->options([
                            'warehouse' => 'En Bodega',
                            'truck' => 'En Camión',
                        ])
                        ->colors([
                            'warehouse' => 'info',
                            'truck' => 'warning',
                        ])
                        ->icons([
                            'warehouse' => 'heroicon-m-building-office',
                            'truck' => 'heroicon-m-truck',
                        ])
                        ->inline()
                        ->visible(fn(Get $get) => $get('type') === 'adjust')
                        ->required(fn(Get $get) => $get('type') === 'adjust')
                        ->live()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('unit_cost')
                        ->label('Costo unitario')
                        ->numeric()
                        ->step(0.01)
                        ->prefix('Q')
                        ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                        ->visible(function (Get $get) {
                            if ($get('type') !== 'in') return false;
                            $productId = $get('product_id');
                            if (!$productId) return true; // Show by default if no product selected yet
                            $product = \App\Models\Product::find($productId);
                            return $product && $product->type !== 'raw_material';
                        })
                        ->columnSpan(1),
                    
                    Forms\Components\Grid::make(2)
                        ->schema([
                            // ─── ORIGEN ───────────────
                            Forms\Components\Select::make('from_warehouse_id')
                                ->label('📍 Bodega de Origen')
                                ->options(Warehouse::query()->where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->live()
                                ->visible(fn(Get $get) => 
                                    $get('type') === 'out' || 
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['load', 'internal_wh']))
                                )
                                ->required(fn(Get $get) => 
                                    $get('type') === 'out' || 
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['load', 'internal_wh']))
                                )
                                ->columnSpan(1),

                            Forms\Components\Select::make('from_truck_id')
                                ->label('🚚 Camión de Origen')
                                ->options(Truck::query()->where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->live()
                                ->visible(fn(Get $get) => 
                                    $get('type') === 'return' ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['unload', 'transshipment']))
                                )
                                ->required(fn(Get $get) => 
                                    $get('type') === 'return' ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['unload', 'transshipment']))
                                )
                                ->columnSpan(1),

                            // ─── DESTINO ──────────────
                            Forms\Components\Select::make('to_warehouse_id')
                                ->label(fn($get) => $get('type') === 'adjust' ? '📍 Seleccione Bodega a Corregir' : '🎯 Bodega de Destino')
                                ->options(Warehouse::query()->where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn(Get $get) => 
                                    $get('type') === 'in' || 
                                    $get('type') === 'return' ||
                                    ($get('type') === 'adjust' && $get('adjustment_location_type') === 'warehouse') ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['unload', 'internal_wh']))
                                )
                                ->required(fn(Get $get) => 
                                    $get('type') === 'in' || 
                                    $get('type') === 'return' ||
                                    ($get('type') === 'adjust' && $get('adjustment_location_type') === 'warehouse') ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['unload', 'internal_wh']))
                                )
                                ->columnSpan(1),

                            Forms\Components\Select::make('to_truck_id')
                                ->label(fn($get) => $get('type') === 'adjust' ? '🚚 Seleccione Camión a Corregir' : '🎯 Camión de Destino')
                                ->options(Truck::query()->where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn(Get $get) => 
                                    ($get('type') === 'adjust' && $get('adjustment_location_type') === 'truck') ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['load', 'transshipment']))
                                )
                                ->required(fn(Get $get) => 
                                    ($get('type') === 'adjust' && $get('adjustment_location_type') === 'truck') ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['load', 'transshipment']))
                                )
                                ->columnSpan(1),
                        ])
                        ->visible(fn(Get $get) => in_array($get('type'), ['in', 'out', 'transfer', 'return', 'adjust'])),

                    // ASOCIACIÓN A DESPACHO (Opcional para agilizar carga)
                    Forms\Components\Select::make('source_id')
                        ->label('Despacho Asociado')
                        ->placeholder('Opcional: Vincular a pedido/despacho')
                        ->options(\App\Models\Dispatch::where('status', 'pending')->pluck('dispatch_number', 'id'))
                        ->searchable()
                        ->visible(fn(Get $get) => $get('type') === 'transfer' && $get('motive') === 'load')
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $set('source_type', $state ? 'dispatch' : null))
                        ->live(),

                    Forms\Components\Hidden::make('source_type'),

                    Forms\Components\Textarea::make('note')
                        ->label('Nota / Motivo Detallado')
                        ->rows(3)
                        ->required(fn(Get $get) => $get('type') === 'adjust')
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn() => auth()->id())
                        ->dehydrated(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'in'       => 'Entrada',
                        'out'      => 'Salida',
                        'adjust'   => 'Ajuste',
                        'transfer' => 'Transferencia',
                        'return'   => 'Devolución',
                        default    => $state,
                    })
                    ->colors([
                        'success' => 'in',
                        'danger'  => 'out',
                        'warning' => 'adjust',
                        'info'    => 'transfer',
                        'primary' => 'return',
                    ]),

                Tables\Columns\TextColumn::make('motive')
                    ->label('Motivo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'load' => 'Carga Camión',
                        'unload' => 'Descarga Camión',
                        'internal_wh' => 'Entre Bodegas',
                        'transshipment' => 'Trasbordo',
                        default => '—',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cant. Base')
                    ->formatStateUsing(fn($state) => number_format(abs((float)$state), 2, '.', ','))
                    ->description(fn(InventoryMovement $record) => 
                        ($record->type === 'adjust' && $record->quantity < 0) ? 'Resta' : ''
                    ),

                Tables\Columns\TextColumn::make('origen')
                    ->label('Origen')
                    ->state(fn (InventoryMovement $record) => $record->fromWarehouse?->name ?? $record->fromTruck?->name ?? '—'),

                Tables\Columns\TextColumn::make('destino')
                    ->label('Destino')
                    ->state(fn (InventoryMovement $record) => $record->toWarehouse?->name ?? $record->toTruck?->name ?? '—'),

                Tables\Columns\TextColumn::make('source_id')
                    ->label('Origen Doc.')
                    ->toggleable()
                    ->formatStateUsing(fn ($state, $record) => $record->source_type === 'dispatch' ? 'Despacho' : '—')
                    ->description(function ($record) {
                        if ($record->source_type === 'dispatch') {
                            $dispatch = \App\Models\Dispatch::find($record->source_id);
                            return $dispatch ? $dispatch->dispatch_number : "#{$record->source_id}";
                        }
                        return null;
                    })
                    ->color('gray')
                    ->icon('heroicon-m-document-text')
                    ->url(function ($record) {
                        if ($record->source_type === 'dispatch') {
                            return \App\Filament\Resources\DispatchResource::getUrl('view', ['record' => $record->source_id]);
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Usuario')
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('gray')
                    ->color('gray')
                    ->toggleable(),
            ])
            ->filters([
                // Filtro visible solo si NO estamos en modo contextual
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'in' => 'Entrada', 
                        'out' => 'Salida', 
                        'adjust' => 'Ajuste', 
                        'transfer' => 'Transferencia'
                    ])
                    ->visible(fn() => !request()->has('type')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Delete revierte el stock gracias al Observer
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['product', 'fromWarehouse', 'fromTruck', 'toWarehouse', 'toTruck', 'creator']);

        // 🚀 FILTRADO AUTOMÁTICO POR URL
        if ($type = request()->query('type')) {
            $query->where('type', $type);
        }

        return $query;
    }
    
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
            'edit'   => Pages\EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}
