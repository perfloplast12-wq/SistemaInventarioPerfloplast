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
        return auth()->user()?->can('inventory_movements.view') ?? false;
    }

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        $type = request()->query('type');

        return $form->schema([
            Forms\Components\Section::make('Guía de Uso')
                ->collapsible()
                ->compact()
                ->schema([
                    Forms\Components\Placeholder::make('guide')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                                <p class="font-bold">¿Cuándo usar este módulo?</p>
                                <ul class="list-disc ml-5 mt-1 text-sm">
                                    <li><b>Entradas:</b> Para registrar compras de materia prima.</li>
                                    <li><b>Transferencias:</b> Para mover carga al camión (Carga) o entre bodegas.</li>
                                    <li><b>Ajustes:</b> Solo para corregir errores de inventario físico.</li>
                                </ul>
                                <p class="mt-2 text-xs italic italic">Nota: Las Ventas y los Despachos de pedidos se registran automáticamente en sus propios módulos.</p>
                            </div>
                        ')),
                ]),

            Forms\Components\Section::make('Detalles del Producto')
                ->schema([
                    Forms\Components\ToggleButtons::make('product_category')
                        ->label('Categoría a Mover')
                        ->options([
                            'raw_material' => 'Materia Prima',
                            'finished_product' => 'Producto Terminado',
                        ])
                        ->colors([
                            'raw_material' => 'info',
                            'finished_product' => 'success',
                        ])
                        ->inline()
                        ->required()
                        ->live()
                        ->default(function () {
                            $t = request()->query('type') ?? request()->route('record')?->type;
                            if ($t === 'in') return 'raw_material';
                            if ($t === 'out') return 'finished_product';
                            return null;
                        })
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('product_id', null))
                        ->columnSpan(2),

                    Forms\Components\Select::make('product_id')
                        ->label('Producto')
                        ->options(function (Get $get) {
                            $cat = $get('product_category');
                            if (!$cat) return [];
                            return \App\Models\Product::where('type', $cat)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->minValue(0.001)
                        ->required()
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            $qty = (float) $state;
                            if ($get('type') === 'adjust' && $get('adjustment_direction') === 'subtract') {
                                return -$qty;
                            }
                            return $qty;
                        })
                        ->columnSpan(2),
                ])->columns(1),

            Forms\Components\Section::make('Configuración del Movimiento')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo de movimiento')
                        ->options([
                            'in'       => 'Entrada (Compra de Materia Prima)',
                            'out'      => 'Salida (Consumo Manual / Venta Directa)',
                            'transfer' => 'Transferencia (Cargar Camión / Entre Bodegas)',
                            'return'   => 'Devolución (Sobrante de Camión -> Bodega)',
                            'adjust'   => 'Ajuste (Corrección por Daño o Pérdida)',
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
                        ->label('Acción de Ajuste')
                        ->options([
                            'add'      => 'Sumar al stock (+)',
                            'subtract' => 'Restar del stock (-)',
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'adjust')
                        ->required(fn (Get $get) => $get('type') === 'adjust')
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('unit_cost')
                        ->label('Costo unitario')
                        ->numeric()
                        ->prefix('Q')
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
                                ->visible(fn(Get $get) => 
                                    $get('type') === 'out' || 
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['load', 'internal_wh']))
                                )
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\Select::make('from_truck_id')
                                ->label('🚚 Camión de Origen')
                                ->options(Truck::query()->where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn(Get $get) => 
                                    $get('type') === 'return' ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['unload', 'transshipment']))
                                )
                                ->required()
                                ->columnSpan(1),

                            // ─── DESTINO ──────────────
                            Forms\Components\Select::make('to_warehouse_id')
                                ->label(fn(Get $get) => $get('type') === 'adjust' ? '📍 Ubicación a Corregir' : '🎯 Bodega de Destino')
                                ->options(Warehouse::query()->where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn(Get $get) => 
                                    $get('type') === 'in' || 
                                    $get('type') === 'return' ||
                                    ($get('type') === 'adjust' && !$get('to_truck_id')) ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['unload', 'internal_wh']))
                                )
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\Select::make('to_truck_id')
                                ->label(fn(Get $get) => $get('type') === 'adjust' ? '🚚 Camión a Corregir' : '🎯 Camión de Destino')
                                ->options(Truck::query()->where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn(Get $get) => 
                                    ($get('type') === 'adjust' && !$get('to_warehouse_id')) ||
                                    ($get('type') === 'transfer' && in_array($get('motive'), ['load', 'transshipment']))
                                )
                                ->required()
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
                    ->formatStateUsing(fn($state) => number_format(abs((float)$state), 2))
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
