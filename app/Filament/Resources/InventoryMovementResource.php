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
                                ->options(function () {
                                     return \App\Models\Product::where('is_active', true)
                                         ->get()
                                         ->mapWithKeys(fn($p) => [
                                             $p->id => $p->name . ($p->type === 'raw_material' ? ' (Materia Prima)' : ' (Prod. Terminado)')
                                         ]);
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Get $get) {
                                     if (!$state) return;
                                     $product = \App\Models\Product::find($state);
                                     if ($product) {
                                         $set('product_category', $product->type);
                                         $set('color_id', null);
                                         if (!$get('type')) {
                                             if ($product->type === 'raw_material') $set('type', 'in');
                                             if ($product->type === 'finished_product') $set('type', 'transfer');
                                         }
                                     }
                                 })
                                ->columnSpan(1),

                            Forms\Components\Select::make('color_id')
                                ->label('Color / Variante')
                                ->options(function(Get $get) {
                                    $productId = $get('product_id');
                                    if (!$productId) return [];

                                    $fromWh = $get('from_warehouse_id');
                                    $fromTr = $get('from_truck_id');

                                    $query = \App\Models\Stock::query()
                                        ->where('product_id', $productId)
                                        ->with('color');
                                    
                                    if ($fromWh) $query->where('warehouse_id', $fromWh);
                                    if ($fromTr) $query->where('truck_id', $fromTr);

                                    return $query->get()
                                        ->mapWithKeys(function($s) {
                                            $label = $s->color ? $s->color->display_name : '(Sin Variante)';
                                            $qty = number_format($s->quantity, 2);
                                            return [$s->color_id ?? 'null' => "{$label} — [Disp: {$qty}]"];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get) => $get('product_category') === 'finished_product')
                                ->visible(fn (Get $get) => $get('product_category') === 'finished_product')
                                ->dehydrateStateUsing(fn ($state) => $state === 'null' ? null : $state)
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad a Operar')
                                ->numeric()
                                ->placeholder('0.00')
                                ->extraInputAttributes(['step' => '0.01'])
                                ->required()
                                ->minValue(0.01)
                                ->live()
                                ->columnSpan(1),
                            
                            Forms\Components\Placeholder::make('stock_available_hint')
                                ->label('Estatus de Inventario')
                                ->content(function (Get $get, $record) {
                                    $productId = $get('product_id');
                                    $colorId = $get('color_id') === 'null' ? null : $get('color_id');
                                    $fromWh = $get('from_warehouse_id');
                                    $fromTr = $get('from_truck_id');

                                    if (!$productId || (!$fromWh && !$fromTr)) {
                                        return new \Illuminate\Support\HtmlString("
                                            <div class='flex items-center p-3 bg-gray-50 rounded-lg border border-gray-100'>
                                                <div class='w-2 h-2 bg-gray-400 rounded-full animate-pulse mr-3'></div>
                                                <span class='text-sm text-gray-500 italic'>Seleccione ubicación de origen para ver existencias...</span>
                                            </div>
                                        ");
                                    }

                                    $stock = (float) (\App\Models\Stock::query()
                                        ->where('product_id', $productId)
                                        ->where('color_id', $colorId)
                                        ->where('warehouse_id', $fromWh)
                                        ->where('truck_id', $fromTr)
                                        ->value('quantity') ?? 0);
                                    
                                    if ($record && $record->product_id == $productId && $record->color_id == $colorId) {
                                        if ($record->from_warehouse_id == $fromWh && $record->from_truck_id == $fromTr) {
                                            $stock += abs((float)$record->quantity);
                                        }
                                    }

                                    $colorName = $colorId ? (\App\Models\Color::find($colorId)?->name ?? 'N/A') : 'Base';
                                    $colorClass = $stock > 0 ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-rose-600 bg-rose-50 border-rose-100';
                                    
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='flex flex-col p-3 rounded-lg border {$colorClass}'>
                                            <div class='flex items-baseline justify-between'>
                                                <span class='text-xs uppercase tracking-wider font-bold opacity-70'>Existencia Disponible</span>
                                                <span class='text-3xl font-black'>" . number_format($stock, 2) . "</span>
                                            </div>
                                            <div class='mt-1 text-xs font-medium'>Variante seleccionada: <b>{$colorName}</b></div>
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
                        'transfer' => 'Traslado',
                        'return'   => 'Retorno',
                        default    => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'in'       => 'success',
                        'out'      => 'danger',
                        'adjust'   => 'warning',
                        'transfer' => 'info',
                        'return'   => 'primary',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->description(fn (?InventoryMovement $record) => $record?->color?->display_name)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->formatStateUsing(fn($state) => number_format(abs((float)$state), (round((float)$state) == (float)$state ? 0 : 2), '.', ','))
                    ->color(fn(?InventoryMovement $record) => $record && $record->quantity < 0 ? 'danger' : 'success')
                    ->weight('black')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('origen')
                    ->label('📍 Origen')
                    ->state(fn (?InventoryMovement $record) => $record?->fromWarehouse?->name ?? $record?->fromTruck?->name ?? '—')
                    ->icon(fn ($record) => $record?->fromWarehouse ? 'heroicon-m-building-office' : ($record?->fromTruck ? 'heroicon-m-truck' : null))
                    ->color(fn ($record) => $record?->fromWarehouse ? 'info' : ($record?->fromTruck ? 'warning' : 'gray')),

                Tables\Columns\TextColumn::make('destino')
                    ->label('🎯 Destino')
                    ->state(fn (?InventoryMovement $record) => $record?->toWarehouse?->name ?? $record?->toTruck?->name ?? '—')
                    ->icon(fn ($record) => $record?->toWarehouse ? 'heroicon-m-building-office' : ($record?->toTruck ? 'heroicon-m-truck' : null))
                    ->color(fn ($record) => $record?->toWarehouse ? 'info' : ($record?->toTruck ? 'warning' : 'gray')),

                Tables\Columns\TextColumn::make('source_info')
                    ->label('Referencia')
                    ->badge()
                    ->state(function ($record) {
                        if (!$record) return '—';
                        if ($record->source_type === 'dispatch' && $record->source_id) {
                            $dispatch = \App\Models\Dispatch::find($record->source_id);
                            return $dispatch ? "Despacho: {$dispatch->dispatch_number}" : "Despacho #{$record->source_id}";
                        }
                        if ($record->source_type === 'sale' && $record->source_id) {
                            $sale = \App\Models\Sale::find($record->source_id);
                            return $sale ? "Venta: {$sale->sale_number}" : "Venta #{$record->source_id}";
                        }
                        return $record->note ? 'Nota' : 'Manual';
                    })
                    ->color(fn ($record) => match ($record?->source_type) {
                        'dispatch' => 'primary',
                        'sale' => 'success',
                        default => 'gray',
                    })
                    ->url(function ($record) {
                        if (!$record) return null;
                        if ($record->source_type === 'dispatch' && $record->source_id) {
                            return \App\Filament\Resources\DispatchResource::getUrl('view', ['record' => $record->source_id]);
                        }
                        if ($record->source_type === 'sale' && $record->source_id) {
                            return \App\Filament\Resources\SaleResource::getUrl('view', ['record' => $record->source_id]);
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Usuario')
                    ->icon('heroicon-m-user-circle')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferLoading()
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
                Tables\Actions\EditAction::make()
                    ->visible(fn (?InventoryMovement $record) => 
                        $record && $record->source_type === null && 
                        $record->created_at?->gt(now()->subHours(24))
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (?InventoryMovement $record) => 
                        $record && $record->source_type === null && 
                        $record->created_at?->gt(now()->subHours(24))
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['product', 'color', 'fromWarehouse', 'fromTruck', 'toWarehouse', 'toTruck', 'creator']);

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
