<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionResource\Pages;
use App\Models\Production;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Producciones';
    protected static ?string $modelLabel = 'Producción';
    protected static ?string $pluralModelLabel = 'Producciones';

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'INVENTARIO Y PRODUCCIÓN';

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('production.view') ?? false) && !auth()->user()?->hasRole('sales');
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('production.create') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Principal')
                    ->schema([
                        Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'md' => 3])
                            ->schema([
                                Forms\Components\TextInput::make('production_number')
                                    ->label('Nro. Producción')
                                    ->placeholder('Autogenerado')
                                    ->disabled()
                                    ->dehydrated(false),
                                
                                Forms\Components\DateTimePicker::make('production_date')
                                    ->label('Fecha de Producción')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!$state) return;
                                        $hour = \Carbon\Carbon::parse($state)->format('H:i');
                                        $shifts = \App\Models\Shift::where('is_active', true)->get();
                                        foreach ($shifts as $shift) {
                                            if (!$shift->start_time || !$shift->end_time) continue;
                                            $start = $shift->start_time;
                                            $end = $shift->end_time;
                                            if ($start > $end) {
                                                if ($hour >= $start || $hour < $end) {
                                                    $set('shift_id', $shift->id);
                                                    return;
                                                }
                                            } else {
                                                if ($hour >= $start && $hour < $end) {
                                                    $set('shift_id', $shift->id);
                                                    return;
                                                }
                                            }
                                        }
                                    }),
                                
                                Forms\Components\Select::make('shift_id')
                                    ->label('Turno')
                                    ->relationship('shift', 'name')
                                    ->options(fn () => \App\Models\Shift::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->dehydrated(true)
                                    ->hint('Se detecta automáticamente según la hora')
                                    ->default(function () {
                                        $hour = now()->format('H:i');
                                        $shifts = \App\Models\Shift::where('is_active', true)->get();
                                        foreach ($shifts as $shift) {
                                            if (!$shift->start_time || !$shift->end_time) continue;
                                            $start = $shift->start_time;
                                            $end = $shift->end_time;
                                            if ($start > $end) {
                                                if ($hour >= $start || $hour < $end) return $shift->id;
                                            } else {
                                                if ($hour >= $start && $hour < $end) return $shift->id;
                                            }
                                        }
                                        return null;
                                    }),
                            ]),

                        Forms\Components\Select::make('to_warehouse_id')
                            ->label('Bodega de Destino (Para Ingreso de Stock)')
                            ->options(fn () => \App\Models\Warehouse::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\Placeholder::make('status_display')
                            ->label('Estado Actual')
                            ->content(fn ($record) => match ($record?->status) {
                                'confirmed' => '✓ Confirmada (Inventario actualizado)',
                                'cancelled' => '✗ Cancelada',
                                default     => '⋯ Borrador (No ha afectado el inventario aún)',
                            })
                            ->extraAttributes(['class' => 'text-sm font-medium']),
                    ])->columns(1),

                Forms\Components\Section::make('Productos Finalizados (Lo que se fabricó)')
                    ->schema([
                        Forms\Components\Repeater::make('outputs')
                            ->label('Ingreso de Producto Terminado')
                            ->relationship('outputs')
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Producto Terminado')
                                            ->options(fn () => \App\Models\Product::where('type', 'finished_product')
                                                ->where('is_active', true)
                                                ->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(['default' => 12, 'md' => 5]),
                                        
                                        Forms\Components\Select::make('color_id')
                                            ->label('Color / Variante')
                                            ->relationship('color', 'name', fn ($query) => $query->where('is_active', true))
                                            ->searchable(['name', 'code'])
                                            ->preload()
                                            ->required()
                                            ->columnSpan(['default' => 12, 'md' => 4]),

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cant. Producida')
                                            ->numeric()
                                            ->step(0.01)
                                            ->required()
                                            ->minValue(0.01)
                                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                            ->columnSpan(['default' => 12, 'md' => 3]),

                                        Forms\Components\Hidden::make('type')->default('output'),
                                    ]),
                            ])
                            ->addActionLabel('Añadir OTRO Producto Producido')
                            ->itemLabel(fn (array $state): ?string => 
                                \App\Models\Product::find($state['product_id'] ?? null)?->name ?? 'Nuevo Producto'
                            )
                            ->collapsible()
                            ->minItems(1),
                    ]),

                Forms\Components\Section::make('Materias Primas Consumidas (Consumibles)')
                    ->schema([
                        Forms\Components\Repeater::make('consumables')
                            ->label('Salida de Materia Prima')
                            ->relationship('consumables')
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                         Forms\Components\Select::make('product_id')
                                            ->label('Materia Prima')
                                            ->options(fn () => \App\Models\Product::where('type', 'raw_material')
                                                ->where('is_active', true)
                                                ->with(['stocks.warehouse'])
                                                ->get()
                                                ->mapWithKeys(function ($p) {
                                                    $stocks = $p->stocks ?? collect();
                                                    $stockByWh = $stocks->where('quantity', '>', 0)
                                                        ->groupBy('warehouse_id')
                                                        ->map(function ($group) {
                                                            $first = $group->first();
                                                            $whName = $first && $first->warehouse ? $first->warehouse->name : 'Bodega';
                                                            $total = $group->sum('quantity');
                                                            return "{$whName}: " . number_format($total, 0);
                                                        })
                                                        ->implode(' | ');
                                                    
                                                    $label = $p->name . ($stockByWh ? " — [{$stockByWh}]" : " — [Sin Stock]");
                                                    return [$p->id => $label];
                                                })
                                            )
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->columnSpan(['default' => 12, 'md' => 12]),
                                        
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad Consumo')
                                            ->numeric()
                                            ->step(0.01)
                                            ->required()
                                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                            ->minValue(0.01)
                                            ->columnSpan(['default' => 12, 'md' => 5]),

                                        Forms\Components\Placeholder::make('stock_hint')
                                            ->label('')
                                            ->content(function (Get $get) {
                                                $productId = $get('product_id');
                                                $selectedWarehouseId = $get('../../to_warehouse_id'); 
                                                
                                                if (!$productId) return 'Seleccione un producto...';

                                                $product = \App\Models\Product::find($productId);
                                                if (!$product) return '';

                                                $stocks = \App\Models\Stock::where('product_id', $productId)
                                                    ->where('quantity', '>', 0)
                                                    ->with(['warehouse', 'truck', 'color'])
                                                    ->get();

                                                if ($stocks->isEmpty()) {
                                                    return new \Illuminate\Support\HtmlString("<div class='text-xs text-danger-600 italic'>Sin existencias en ninguna bodega.</div>");
                                                }

                                                $html = "<div class='text-xs bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 space-y-1'>";
                                                $html .= "<div class='font-bold text-gray-700 dark:text-gray-300 border-b pb-1 mb-1'>Disponibilidad actual:</div>";
                                                
                                                foreach ($stocks as $s) {
                                                    $locName = $s->warehouse?->name ?? ($s->truck?->name ?? 'Desconocido');
                                                    $isMatch = $s->warehouse_id == $selectedWarehouseId;
                                                    $colorInfo = $s->color_id ? " (" . ($s->color?->name ?? 'Color') . ")" : "";
                                                    
                                                    $colorClass = $isMatch ? 'text-success-600 font-bold' : 'text-gray-500 dark:text-gray-400';
                                                    $indicator = $isMatch ? '➔' : '•';
                                                    
                                                    $html .= "<div class='{$colorClass}'>{$indicator} {$locName}{$colorInfo}: " . number_format($s->quantity, 2) . "</div>";
                                                }

                                                if (!$stocks->contains('warehouse_id', $selectedWarehouseId)) {
                                                    $html .= "<div class='text-danger-500 text-[10px] pt-1'>⚠ No hay stock en la bodega de destino seleccionada.</div>";
                                                }

                                                $html .= "</div>";
                                                return new \Illuminate\Support\HtmlString($html);
                                            })
                                            ->columnSpan(12),

                                        Forms\Components\Hidden::make('type')->default('consumable'),
                                    ]),
                            ])
                            ->addActionLabel('Añadir Materia Prima')
                            ->itemLabel(fn (array $state): ?string => 
                                \App\Models\Product::find($state['product_id'] ?? null)?->name ?? 'Nuevo Item'
                            )
                            ->collapsible()
                            ->minItems(1),
                    ]),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('Observaciones')
                            ->rows(2)
                            ->placeholder('Opcional...'),
                    ])->collapsed(),
                
                Forms\Components\Hidden::make('created_by')->default(auth()->id()),
                Forms\Components\Hidden::make('status')->default('draft'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('production_number')
                    ->label('Nro. Prod.')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->weight('bold')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('production_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('outputs_summary')
                    ->label('Fabricado')
                    ->state(fn ($record) => $record->outputs->count() . " items")
                    ->description(function ($record) {
                        return $record->outputs->take(1)->map(function ($o) {
                            $productName = optional($o->product)->name ?? 'Producto Eliminado';
                            $color = '';
                            
                            if ($o->color) {
                                $colorName = $o->color->display_name ?? $o->color->name ?? '';
                                if (str_contains($colorName, ' (')) {
                                    $colorName = explode(' (', $colorName)[0];
                                }
                                $colorName = ucfirst($colorName);
                                
                                $cleanColorName = strtolower($colorName);
                                $cleanProdName = strtolower($productName);
                                
                                // Only append if color name is not already contained in the product name
                                if (!str_contains($cleanProdName, "({$cleanColorName})") && 
                                    !str_contains($cleanProdName, $cleanColorName)) {
                                    $color = " ({$colorName})";
                                }
                            }
                            return "• {$productName}{$color}";
                        })->implode("\n");
                    })
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('toWarehouse.name')
                    ->label('📍 Destino')
                    ->icon('heroicon-m-building-office')
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Turno')
                    ->icon('heroicon-m-clock')
                    ->color('gray')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'confirmed' => 'Confirmada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Borrador',
                        'confirmed' => 'Confirmada',
                        'cancelled' => 'Cancelada',
                    ]),

                Tables\Filters\SelectFilter::make('shift_id')
                    ->label('Turno')
                    ->relationship('shift', 'name'),

                Tables\Filters\Filter::make('production_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('production_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('production_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
                
                Tables\Actions\Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Confirmar producción?')
                    ->modalDescription('Esto descontará materias primas e ingresará TODOS los productos terminados.')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function (Production $record) {
                        try {
                            $record->confirm();

                            Notification::make()
                                ->title('Producción confirmada')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al confirmar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Cancelar producción?')
                    ->modalDescription('Esto revertirá el stock (devolverá materias primas y descontará los productos terminados).')
                    ->visible(fn ($record) => $record->status === 'confirmed')
                    ->action(function (Production $record) {
                        $record->cancel();

                        Notification::make()
                            ->title('Producción cancelada')
                            ->body('El inventario ha sido revertido con éxito.')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\ProductionExport($records), 
                            "Produccion_Perfloplast_" . now()->format('Ymd_His') . ".xlsx"
                        );
                    }),
            ])
            ->poll('30s')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductions::route('/'),
            'create' => Pages\CreateProduction::route('/create'),
            'edit' => Pages\EditProduction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['outputs.product', 'outputs.color', 'consumables.product', 'shift', 'toWarehouse']);
    }
}
