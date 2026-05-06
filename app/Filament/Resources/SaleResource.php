<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Truck;
use App\Models\Warehouse;
use App\Services\SaleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Ventas';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'ÁREA COMERCIAL';
    
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('sales.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sales.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return (auth()->user()?->can('sales.edit') ?? false) && $record->status === 'draft';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'lg' => 12])
                    ->schema([
                        // Sección Izquierda: Info de Venta
                        Forms\Components\Section::make('Información General')
                            ->schema([
                                Forms\Components\TextInput::make('sale_number')
                                    ->label('Nro. Venta')
                                    ->placeholder('Autogenerado')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'font-mono font-bold']),

                                Forms\Components\DateTimePicker::make('sale_date')
                                    ->label('Fecha y Hora')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\TextInput::make('customer_name')
                                    ->label('Nombre del Cliente')
                                    ->required()
                                    ->minLength(3)
                                    ->default('Consumidor Final'),

                                Forms\Components\TextInput::make('customer_nit')
                                    ->label('NIT')
                                    ->maxLength(20)
                                    ->default('C/F'),

                                Forms\Components\TextInput::make('delivery_address')
                                    ->label('Dirección de Entrega')
                                    ->placeholder('Ciudad, zona, calle, etc.')
                                    ->prefixIcon('heroicon-m-map-pin')
                                    ->required(),

                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono de Contacto')
                                    ->tel()
                                    ->placeholder('44556677')
                                    ->prefixIcon('heroicon-m-phone'),

                                Forms\Components\ToggleButtons::make('origin_type')
                                    ->label('Tipo de Operación')
                                    ->options([
                                        'warehouse' => '📦 Preventa (Desde Bodega)',
                                        'truck' => '🚚 Venta en Ruta (Desde Camión)',
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
                                    ->default('warehouse')
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('from_warehouse_id')
                                    ->label('Bodega Origen')
                                    ->required(fn(Get $get) => $get('origin_type') === 'warehouse')
                                    ->visible(fn(Get $get) => $get('origin_type') === 'warehouse')
                                    ->options(\App\Models\Warehouse::where('is_active', true)->pluck('name', 'id'))
                                    ->default(fn() => \App\Models\Warehouse::where('is_factory', true)->first()?->id)
                                    ->searchable()
                                    ->live(),

                                Forms\Components\Select::make('from_truck_id')
                                    ->label('Camión Origen')
                                    ->required(fn(Get $get) => $get('origin_type') === 'truck')
                                    ->visible(fn(Get $get) => $get('origin_type') === 'truck')
                                    ->options(\App\Models\Truck::where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn ($truck) => [$truck->id => $truck->name . " [" . ($truck->plate ?? 'N/A') . "]"]))
                                    ->searchable()
                                    ->live(),

                                Forms\Components\Textarea::make('note')
                                    ->label('Notas / Observaciones')
                                    ->placeholder('Escriba aquí cualquier detalle relevante...')
                                    ->rows(2),
                            ])->columnSpan(['default' => 'full', 'lg' => 4]),

                        // Sección Derecha: Items
                        Forms\Components\Section::make('Productos / Items')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Grid::make(12)
                                            ->schema([
                                                Forms\Components\Select::make('product_id')
                                                    ->label('Producto')
                                                    ->options(function (Get $get) {
                                                        $originType = $get('../../origin_type');
                                                        $truckId = $get('../../from_truck_id');
                                                        $warehouseId = $get('../../from_warehouse_id');
                                                        
                                                        $query = \App\Models\Product::where('type', 'finished_product')->where('is_active', true);
                                                        
                                                        if ($originType === 'truck' && $truckId) {
                                                            $query->whereHas('stocks', fn($q) => $q->where('truck_id', $truckId)->where('quantity', '>', 0));
                                                        } elseif ($originType === 'warehouse' && $warehouseId) {
                                                            $query->whereHas('stocks', fn($q) => $q->where('warehouse_id', $warehouseId)->where('quantity', '>', 0));
                                                        } else {
                                                            return [];
                                                        }

                                                        return $query->pluck('name', 'id')->toArray();
                                                    })
                                                    ->required()
                                                    ->searchable()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $set('color_id', null);
                                                        if (!$state) {
                                                            $set('unit_price', null);
                                                            $set('quantity', null);
                                                            $set('subtotal', 0);
                                                            return;
                                                        }
                                                        $product = \App\Models\Product::find($state);
                                                        $price = $product ? (float)$product->sale_price : 0;
                                                        $set('unit_price', $price);
                                                        if (!$get('quantity')) {
                                                            $set('quantity', 1);
                                                            $set('subtotal', $price);
                                                        } else {
                                                            $set('subtotal', $price * (float)$get('quantity'));
                                                        }
                                                    })
                                                    ->columnSpan(['default' => 12, 'md' => 4]),

                                                Forms\Components\Select::make('color_id')
                                                    ->label('Color')
                                                    ->options(function (Get $get) {
                                                        $productId = $get('product_id');
                                                        $originType = $get('../../origin_type');
                                                        $truckId = $get('../../from_truck_id');
                                                        $warehouseId = $get('../../from_warehouse_id');
                                                        
                                                        if (!$productId || (!$truckId && !$warehouseId)) return [];

                                                        $product = \App\Models\Product::with('color')->find($productId);
                                                        
                                                        $query = \App\Models\Stock::with('color')->where('product_id', $productId)->where('quantity', '>', 0);
                                                        if ($originType === 'truck') $query->where('truck_id', $truckId);
                                                        else $query->where('warehouse_id', $warehouseId);
                                                        
                                                        $stocks = $query->get();

                                                        return $stocks->groupBy(fn($s) => $s->color_id ?? 'null')->mapWithKeys(function ($group, $key) use ($product) {
                                                            $totalQty = $group->sum('quantity');
                                                            $stockRecord = $group->first();
                                                            $stockColor = $stockRecord->color;
                                                            $colorLabel = $stockColor ? $stockColor->display_name : ($product->color ? $product->color->display_name . ' (Catálogo)' : 'Sin Color');
                                                            
                                                            return [
                                                                $key => $colorLabel . " — [Disp: " . number_format($totalQty, 2) . "]"
                                                            ];
                                                        })->toArray();
                                                    })
                                                    ->required()
                                                    ->searchable()
                                                    ->live()
                                                    ->columnSpan(['default' => 12, 'md' => 4]),

                                                Forms\Components\Placeholder::make('stock_availability')
                                                    ->label('Existencia Actual')
                                                    ->content(function (Get $get) {
                                                        $productId = $get('product_id');
                                                        $colorId = $get('color_id');
                                                        $originType = $get('../../origin_type');
                                                        $truckId = $get('../../from_truck_id');
                                                        $warehouseId = $get('../../from_warehouse_id');

                                                        if (!$productId || (!$truckId && !$warehouseId)) return 'Seleccione producto y color';

                                                        $query = \App\Models\Stock::where('product_id', $productId);
                                                        if ($colorId) $query->where('color_id', $colorId);
                                                        else $query->whereNull('color_id');

                                                        if ($originType === 'truck') $query->where('truck_id', $truckId);
                                                        else $query->where('warehouse_id', $warehouseId);

                                                        $stock = (float) $query->sum('quantity');
                                                        
                                                        $color = $stock > 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400';
                                                        $label = $stock > 0 ? '✅ Disponible: ' : '❌ Agotado: ';

                                                        return new \Illuminate\Support\HtmlString("<span class='font-bold {$color}'>{$label} " . number_format($stock, 2) . "</span>");
                                                    })
                                                    ->columnSpan(['default' => 12, 'md' => 4]),

                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Cant.')
                                                    ->numeric()
                                                    ->step(0.01)
                                                    ->default(1)
                                                    ->minValue(0.01)
                                                    ->required()
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $price = (float)($get('unit_price') ?? 0);
                                                        $qty = (float)($state ?: 0);
                                                        $set('subtotal', $qty * $price);
                                                    })
                                                    ->columnSpan(['default' => 4, 'md' => 4]),

                                                Forms\Components\Placeholder::make('unit_price_display')
                                                    ->label('Precio Q')
                                                    ->content(fn (Get $get) => new \Illuminate\Support\HtmlString("
                                                        <div class='bg-gray-100 dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-700 rounded-lg p-2 text-center text-2xl font-black text-gray-950 dark:text-gray-50 shadow-sm'>
                                                            Q " . number_format((float)($get('unit_price') ?? 0), 2, '.', ',') . "
                                                        </div>
                                                    "))
                                                    ->columnSpan(['default' => 4, 'md' => 4]),

                                                Forms\Components\Placeholder::make('subtotal_display')
                                                    ->label('Subtotal')
                                                    ->content(fn (Get $get) => new \Illuminate\Support\HtmlString("
                                                        <div class='bg-primary-50 dark:bg-primary-950/30 border-2 border-primary-200 dark:border-primary-800/80 rounded-lg p-2 text-right text-2xl font-black text-primary-700 dark:text-primary-300 shadow-sm'>
                                                            Q " . number_format((float)($get('subtotal') ?? 0), 2, '.', ',') . "
                                                        </div>
                                                    "))
                                                    ->columnSpan(['default' => 4, 'md' => 4]),

                                                Forms\Components\Hidden::make('unit_price'),
                                                Forms\Components\Hidden::make('subtotal'),
                                            ]),
                                    ])
                                    ->defaultItems(1)
                                    ->addActionLabel('Añadir OTRO Producto')
                                    ->reorderable(false)
                                    ->live()
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        // Ensure all required fields are explicitly present to prevent SQL constraint errors
                                        $qty = (float)($data['quantity'] ?? 0);
                                        $price = (float)($data['unit_price'] ?? 0);
                                        
                                        $data['unit_price'] = $price;
                                        $data['subtotal'] = (float)($data['subtotal'] ?? ($qty * $price));
                                        $data['discount_amount'] = 0;
                                        $data['total'] = $data['subtotal'];
                                        
                                        // Convert string 'null' or empty strings back to actual null for database
                                        if (empty($data['color_id']) || $data['color_id'] === 'null') {
                                            $data['color_id'] = null;
                                        }
                                        
                                        return $data;
                                    })
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculateTotalsInForm($set, $get)),
                            ])->columnSpan(['default' => 'full', 'lg' => 8]),

                        // Sección Resumen
                        Forms\Components\Section::make('Resumen y Pago')
                            ->schema([
                                Forms\Components\Grid::make(['default' => 1, 'md' => 4])
                                    ->schema([
                                        Forms\Components\Placeholder::make('subtotal_venta_display')
                                            ->label('Subtotal Bruto')
                                            ->content(fn (Get $get) => 'Q ' . number_format(self::calculateSubtotalInForm($get), 2, '.', ','))
                                            ->extraAttributes(['class' => 'text-xl font-bold text-gray-900 dark:text-gray-100']),

                                        Forms\Components\Group::make([
                                            Forms\Components\Select::make('discount_type')
                                                ->label('Tipo Descuento')
                                                ->options([
                                                    'none' => 'N/A',
                                                    'percent' => 'Porcentaje (%)',
                                                    'fixed' => 'Monto Fijo (Q)',
                                                ])
                                                ->default('none')
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculateTotalsInForm($set, $get)),

                                            Forms\Components\TextInput::make('discount_value')
                                                ->label('Valor Descuento')
                                                ->numeric()
                                                ->step(0.01)
                                                ->default(0)
                                                ->minValue(0)
                                                ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                                ->visible(fn (Get $get) => $get('discount_type') !== 'none')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculateTotalsInForm($set, $get)),
                                        ])->columns(2),

                                        Forms\Components\Placeholder::make('total_final_display')
                                            ->label('TOTAL A PAGAR')
                                            ->content(fn (Get $get) => 'Q ' . number_format((float)($get('total') ?? 0), 2, '.', ','))
                                            ->extraAttributes(['class' => 'text-2xl font-black text-primary-600 dark:text-primary-400']),

                                        Forms\Components\Group::make([
                                            Forms\Components\Select::make('payment_method')
                                                ->label('Método de Pago')
                                                ->options([
                                                    'cash' => 'Efectivo',
                                                    'transfer' => 'Transferencia',
                                                    'card' => 'Tarjeta',
                                                ])
                                                ->default('cash'),

                                            Forms\Components\TextInput::make('payment_amount')
                                                ->label('Monto Recibido')
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('Q')
                                                ->live()
                                                ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                                ->default(0),

                                            Forms\Components\Placeholder::make('change_amount_display')
                                                ->label('Vuelto / Cambio')
                                                ->content(function (Get $get) {
                                                    $total = (float) $get('total') ?: 0;
                                                    $received = (float) $get('payment_amount') ?: 0;
                                                    $change = max(0, $received - $total);
                                                    return 'Q ' . number_format($change, 2, '.', ',');
                                                })
                                                ->extraAttributes(['class' => 'text-xl font-bold text-success-600 dark:text-success-400']),
                                        ])->columnSpan(2)->columns(2),
                                    ]),
                                
                                Forms\Components\Hidden::make('discount_amount'),
                                Forms\Components\Hidden::make('total'),
                            ])->columnSpanFull(),
                    ]),

                Forms\Components\Hidden::make('status')->default('draft'),
                Forms\Components\Hidden::make('created_by')->default(fn () => auth()->id()),
            ])
            ->disabled(fn (?Sale $record) => $record && $record->status !== 'draft');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_number')
                    ->label('Nro. Venta')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->description(fn($record) => "NIT: {$record->customer_nit}")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('origin')
                    ->label('Origen')
                    ->state(fn ($record) => $record->fromWarehouse?->name ?? $record->fromTruck?->name ?? '—')
                    ->icon(fn ($record) => $record->origin_type === 'warehouse' ? 'heroicon-m-building-office' : 'heroicon-m-truck')
                    ->color(fn ($record) => $record->origin_type === 'warehouse' ? 'info' : 'warning'),

                Tables\Columns\TextColumn::make('items_summary')
                    ->label('Productos')
                    ->state(function ($record) {
                        return $record->items->count() . " items";
                    })
                    ->description(function ($record) {
                        return $record->items->take(2)->map(function ($item) {
                            $productName = $item->product->name ?? 'Producto';
                            $color = '';
                            
                            if ($item->color) {
                                $colorName = $item->color->display_name ?? $item->color->name ?? '';
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
                    ->wrap(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ','))
                    ->sortable()
                    ->weight('black')
                    ->alignment('right'),

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

                Tables\Columns\TextColumn::make('note')
                    ->label('Observación')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Vendedor')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'confirmed' => 'Confirmada',
                        'cancelled' => 'Cancelada',
                    ]),
                
                Tables\Filters\Filter::make('sale_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('sale_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('sale_date', '<=', $date));
                    }),

                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Vendedor')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (Sale $record) => $record->status === 'draft'),
                    
                    Tables\Actions\Action::make('confirm')
                        ->label('Confirmar Venta')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Sale $record) => $record->status === 'draft')
                        ->action(function (Sale $record, SaleService $service) {
                            try {
                                $service->confirm($record);
                                Notification::make()->title('Venta Confirmada')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                            }
                        }),

                    Tables\Actions\Action::make('record_payment')
                        ->label('Registrar Pago')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('warning')
                        ->visible(fn (Sale $record) => $record->status === 'confirmed' && $record->balance > 0.01)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('Monto a Pagar')
                                ->numeric()
                                ->required()
                                ->prefix('Q')
                                ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                ->default(fn (Sale $record) => $record->balance),
                            Forms\Components\Select::make('method')
                                ->label('Método')
                                ->options([
                                    'cash' => 'Efectivo',
                                    'transfer' => 'Transferencia',
                                    'card' => 'Tarjeta',
                                ])
                                ->required()
                                ->default('cash'),
                            Forms\Components\DatePicker::make('payment_date')
                                ->label('Fecha de Pago')
                                ->default(now())
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notas/Referencia')
                                ->rows(2),
                        ])
                        ->action(function (Sale $record, array $data, SaleService $service) {
                            try {
                                $service->recordPayment($record, $data);
                                Notification::make()->title('Pago Registrado')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                            }
                        }),

                    Tables\Actions\Action::make('download_invoice')
                        ->label('Ticket / Recibo')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->visible(fn (Sale $record) => $record->status === 'confirmed')
                        ->url(fn (Sale $record) => route('sales.invoice.pdf', $record), shouldOpenInNewTab: true),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancelar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Sale $record) => $record->status === 'confirmed')
                        ->action(function (Sale $record, SaleService $service) {
                            try {
                                $service->cancel($record);
                                Notification::make()->title('Venta Cancelada')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\SalesExport($records), 
                            "Ventas_Perfloplast_" . now()->format('Ymd_His') . ".xlsx"
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('15s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
            'view' => Pages\ViewSale::route('/{record}/view'),
            'quick-sale' => Pages\QuickSale::route('/quick'),
            'map' => Pages\SalesMap::route('/map'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['payments', 'creator']);

        if (!auth()->user()?->hasRole(['super_admin', 'admin', 'accounting', 'viewer'])) {
            $query->where('created_by', auth()->id());
        }

        return $query;
    }

    // --- Helpers de Formulario ---
    
    protected static function calculateSubtotalInForm(Get $get): float
    {
        return collect($get('items'))->sum(fn ($i) => (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0));
    }

    protected static function recalculateTotalsInForm(Set $set, Get $get): void
    {
        $subtotal = self::calculateSubtotalInForm($get);
        $type = $get('discount_type');
        $val = (float)($get('discount_value') ?? 0);
        
        $discountAmount = 0;
        if ($type === 'percent') {
            $discountAmount = $subtotal * (min(100, $val) / 100);
        } elseif ($type === 'fixed') {
            $discountAmount = min($subtotal, $val);
        }

        $total = max(0, $subtotal - $discountAmount);

        $set('discount_amount', $discountAmount);
        $set('total', $total);
    }
}
