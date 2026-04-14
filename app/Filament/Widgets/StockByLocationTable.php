<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use App\Models\Truck;
use App\Models\Warehouse;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StockByLocationTable extends TableWidget
{
    protected static ?string $heading = 'Stock por ubicación';
    protected int|string|array $columnSpan = 'full';

    public ?string $locationName = null;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('quantity', 'desc')
            ->columns([

                // ── Producto ─────────────────────────
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                // ── Color ────────────────────────────
                Tables\Columns\TextColumn::make('color.display_name')
                    ->label('Color')
                    ->placeholder('N/A')
                    ->sortable(),

                // ── Tipo badge ───────────────────────
                Tables\Columns\TextColumn::make('location_type_badge')
                    ->label('Tipo')
                    ->badge()
                    ->getStateUsing(fn (Stock $record): string => match (true) {
                        $record->warehouse_id !== null => 'Bodega',
                        $record->truck_id !== null     => 'Camión',
                        default                        => 'N/A',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Bodega' => 'primary',
                        'Camión' => 'warning',
                        default  => 'gray',
                    }),

                // ── Ubicación (nombre) ───────────────
                Tables\Columns\TextColumn::make('location_label')
                    ->label('Ubicación')
                    ->getStateUsing(fn (Stock $record): string => match (true) {
                        $record->warehouse_id !== null => $record->warehouse?->name ?? 'N/A',
                        $record->truck_id !== null     => $record->truck?->name ?? ($record->truck?->plate ?? 'N/A'),
                        default                        => 'Sin ubicación',
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('warehouse', fn (Builder $sub) =>
                                $sub->where('name', 'like', "%{$search}%")
                            )
                            ->orWhereHas('truck', fn (Builder $sub) =>
                                $sub->where('name', 'like', "%{$search}%")
                                    ->orWhere('plate', 'like', "%{$search}%")
                            );
                        });
                    }),

                // ── Existencia ───────────────────────
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Existencia')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->filters([

                // Filtro principal: Bodegas / Camiones
                Tables\Filters\SelectFilter::make('ubicacion_tipo')
                    ->label('Tipo ubicación')
                    ->options([
                        'warehouse' => 'Bodegas',
                        'truck'     => 'Camiones',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, string $value) => match ($value) {
                            'warehouse' => $q->whereNotNull('warehouse_id'),
                            'truck'     => $q->whereNotNull('truck_id'),
                            default     => $q,
                        }
                    )),

                // Filtro por bodega específica
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Bodega')
                    ->options(fn () => Warehouse::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),

                // Filtro por camión específico
                Tables\Filters\SelectFilter::make('truck_id')
                    ->label('Camión')
                    ->options(fn () => Truck::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function getTableQuery(): Builder
    {
        return Stock::query()
            ->with(['product', 'warehouse', 'truck', 'color'])
            ->where('quantity', '!=', 0)
            ->when($this->locationName, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->whereHas('warehouse', fn ($sub) => $sub->where('name', $this->locationName))
                      ->orWhereHas('truck', fn ($sub) => 
                        $sub->where('name', $this->locationName)
                            ->orWhere('plate', $this->locationName)
                      );
                });
            });
    }
}
