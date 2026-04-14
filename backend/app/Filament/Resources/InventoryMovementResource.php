<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use App\Models\Truck;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Movimientos (Kardex)';
    protected static ?string $modelLabel = 'Movimiento';
    protected static ?string $pluralModelLabel = 'Movimientos';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Movimiento')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->required()
                        ->options([
                            'in' => 'Entrada (Compra / Ingreso)',
                            'out' => 'Salida (Venta / Consumo)',
                            'adjust' => 'Ajuste (Corrección)',
                            'transfer' => 'Transferencia (Bodega ↔ Camión)',
                        ])
                        ->default(fn () => request()->string('type')->toString() ?: null)
                        ->reactive(),

                    Forms\Components\Select::make('product_id')
                        ->label('Producto')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('unit_cost')
                        ->label('Costo unitario (solo entradas)')
                        ->numeric()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'in'),

                    // Desde / Hacia (Warehouse)
                    Forms\Components\Select::make('from_warehouse_id')
                        ->label('Desde bodega')
                        ->options(fn () => Warehouse::query()->where('is_active', true)->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => in_array($get('type'), ['out', 'transfer', 'adjust'], true)),

                    Forms\Components\Select::make('to_warehouse_id')
                        ->label('Hacia bodega')
                        ->options(fn () => Warehouse::query()->where('is_active', true)->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => in_array($get('type'), ['in', 'transfer', 'adjust'], true)),

                    // Desde / Hacia (Truck)
                    Forms\Components\Select::make('from_truck_id')
                        ->label('Desde camión')
                        ->options(fn () => Truck::query()->where('is_active', true)->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'transfer'),

                    Forms\Components\Select::make('to_truck_id')
                        ->label('Hacia camión')
                        ->options(fn () => Truck::query()->where('is_active', true)->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'transfer'),

                    Forms\Components\Textarea::make('note')
                        ->label('Nota (opcional)')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),

                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'in' => 'Entrada',
                        'out' => 'Salida',
                        'adjust' => 'Ajuste',
                        'transfer' => 'Transferencia',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('product.name')->label('Producto')->searchable(),

                Tables\Columns\TextColumn::make('quantity')->label('Cantidad')->sortable(),

                Tables\Columns\TextColumn::make('unit_cost')->label('Costo')->money('GTQ')->toggleable(),

                // Mostrar origen y destino legibles
                Tables\Columns\TextColumn::make('origen')
                    ->label('Origen')
                    ->state(function (InventoryMovement $record) {
                        return $record->fromWarehouse?->name
                            ?? $record->fromTruck?->name
                            ?? '—';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('destino')
                    ->label('Destino')
                    ->state(function (InventoryMovement $record) {
                        return $record->toWarehouse?->name
                            ?? $record->toTruck?->name
                            ?? '—';
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'in' => 'Entrada',
                        'out' => 'Salida',
                        'adjust' => 'Ajuste',
                        'transfer' => 'Transferencia',
                    ]),

                Tables\Filters\SelectFilter::make('warehouse')
                    ->label('Bodega (origen/destino)')
                    ->options(fn () => Warehouse::query()->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        if (!($id = $data['value'] ?? null)) return $query;
                        return $query->where(function ($q) use ($id) {
                            $q->where('from_warehouse_id', $id)->orWhere('to_warehouse_id', $id);
                        });
                    }),

                Tables\Filters\SelectFilter::make('truck')
                    ->label('Camión (origen/destino)')
                    ->options(fn () => Truck::query()->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        if (!($id = $data['value'] ?? null)) return $query;
                        return $query->where(function ($q) use ($id) {
                            $q->where('from_truck_id', $id)->orWhere('to_truck_id', $id);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // ?type=in|out|adjust|transfer
        if ($type = request()->string('type')->toString()) {
            $query->where('type', $type);
        }

        // ?warehouse_id=1 (origen o destino)
        if ($warehouseId = request()->integer('warehouse_id')) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('from_warehouse_id', $warehouseId)
                  ->orWhere('to_warehouse_id', $warehouseId);
            });
        }

        // ?truck_id=1 (origen o destino)
        if ($truckId = request()->integer('truck_id')) {
            $query->where(function ($q) use ($truckId) {
                $q->where('from_truck_id', $truckId)
                  ->orWhere('to_truck_id', $truckId);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
            'edit' => Pages\EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}
