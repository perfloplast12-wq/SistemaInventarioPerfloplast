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
    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 1;
    
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

                                Forms\Components\Select::make('from_truck_id')
                                    ->label('Camión Origen (Vendedor)')
                                    ->required()
                                    ->options(\App\Models\Truck::where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn ($truck) => [$truck->id => "{$truck->name} ({$truck->plate})"]))
                                    ->searchable()
                                    ->live(),

                                Forms\Components\Textarea::make('note')
                                    ->label('Notas / Observaciones')
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
                                                        $truckId = $get('../../from_truck_id');
                                                        $products = \App\Models\Product::where('type', 'finished_product')
                                                            ->where('is_active', true)
                                                            ->get();
                                                            
                                                        return $products->mapWithKeys(function ($p) use ($truckId) {
                                                            $stock = $p->stocks()
                                                                ->when($truckId, fn($q) => $q->where('truck_id', $truckId))
                                                                ->sum('quantity');
                                                                
                                                            return [$p->id => "{$p->name} — <span class='text-gray-500 font-bold ml-1'>(" . ($stock + 0) . " total disp.)</span>"];
                                                        })->toArray();
                                                    })
                                                    ->allowHtml()
                                                    ->required()
                                                    ->searchable()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $set('color_id', null); // Reset color on product change
                                                        if (!$state) {
                                                            $set('unit_price', null);
                                                            $set('quantity', null);
                                                            $set('subtotal', 0);
                                                            return;
                                                        }
                                                        $product = \App\Models\Product::find($state);
                                                        $price = $product ? (float)$product->sale_price : 0;
                                                        $qty = (float)($get('quantity') ?: 1);
                                                        $set('unit_price', $price);
                                                        $set('subtotal', $price * $qty);
                                                        if (!$get('quantity')) {
                                                            $set('quantity', 1);
                                                        }
                                                    })
                                                    ->columnSpan(['default' => 12, 'md' => 4]),

                                                Forms\Components\Select::make('color_id')
                                                    ->label('Color')
                                                    ->options(function (Get $get) {
                                                        $productId = $get('product_id');
                                                        $truckId = $get('../../from_truck_id');
                                                        
                                                        if (!$productId) return [];

                                                        $stocks = \App\Models\Stock::with('color')
                                                            ->where('product_id', $productId)
                                                            ->where('quantity', '>', 0)
                                                            ->when($truckId, fn($q) => $q->where('truck_id', $truckId))
                                                            ->get();

                                                        return $stocks->mapWithKeys(fn ($s) => [
                                                            ($s->color_id ?? 'null') => ($s->color?->name ?? 'Sin Color (N/A)') . " — <span class='text-emerald-600 font-bold'>(" . ($s->quantity + 0) . " disp.)</span>"
                                                        ])->toArray();
                                                    })
                                                    ->allowHtml()
                                                    ->required()
                                                    ->searchable()
                                                    ->live()
                                                    ->columnSpan(['default' => 12, 'md' => 3]),

                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Cant.')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0.001)
                                                    ->required()
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $price = (float)($get('unit_price') ?? 0);
                                                        $qty = (float)($state ?: 0);
                                                        $set('subtotal', $qty * $price);
                                                    })
                                                    ->columnSpan(['default' => 4, 'md' => 1]),

                                                Forms\Components\TextInput::make('unit_price')
                                                    ->label('Precio Q')
                                                    ->numeric()
                                                    ->required()
                                                    ->readOnly() 
                                                    ->dehydrated()
                                                    ->prefix('Q')
                                                    ->columnSpan(['default' => 4, 'md' => 2]),

                                                Forms\Components\Placeholder::make('subtotal_display')
                                                    ->label('Subtotal')
                                                    ->content(fn (Get $get) => 'Q ' . number_format((float)($get('subtotal') ?? 0), 2))
                                                    ->extraAttributes(['class' => 'font-bold text-right pt-2'])
                                                    ->columnSpan(['default' => 4, 'md' => 2]),

                                                Forms\Components\Hidden::make('subtotal'),
                                            ]),
                                    ])
                                    ->defaultItems(1)
                                    ->addActionLabel('Añadir OTRO Producto')
                                    ->reorderable(false)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculateTotalsInForm($set, $get)),
                            ])->columnSpan(['default' => 'full', 'lg' => 8]),

                        // Sección Resumen
                        Forms\Components\Section::make('Resumen y Pago')
                            ->schema([
                                Forms\Components\Grid::make(['default' => 1, 'md' => 4])
                                    ->schema([
                                        Forms\Components\Placeholder::make('subtotal_venta_display')
                                            ->label('Subtotal Bruto')
                                            ->content(fn (Get $get) => 'Q ' . number_format(self::calculateSubtotalInForm($get), 2)),

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
                                                ->default(0)
                                                ->minValue(0)
                                                ->visible(fn (Get $get) => $get('discount_type') !== 'none')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculateTotalsInForm($set, $get)),
                                        ])->columns(2),

                                        Forms\Components\Placeholder::make('total_final_display')
                                            ->label('TOTAL A PAGAR')
                                            ->content(fn (Get $get) => 'Q ' . number_format((float)($get('total') ?? 0), 2))
                                            ->extraAttributes(['class' => 'text-2xl font-black text-primary-600']),

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
                                                ->prefix('Q')
                                                ->default(0),
                                        ])->columns(2),
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
                    ->fontFamily('mono')
                    ->copyable(),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('GTQ')
                    ->sortable()
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Pagado')
                    ->money('GTQ')
                    ->color('success')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('GTQ')
                    ->color(fn ($record) => $record->balance > 0.01 ? 'danger' : 'success')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Descuento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'none', null => 'Sin descuento',
                        'percent' => 'Porcentaje',
                        'fixed' => 'Monto Fijo',
                        default => $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'none', null => 'gray',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('payments.method')
                    ->label('Método Pago')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'transfer' => 'Transferencia',
                        'card' => 'Tarjeta',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'card' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'confirmed' => 'Confirmada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),

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
                    Tables\Actions\BulkAction::make('export_excel')
                        ->label('Exportar a Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('green')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $filename = "ventas_seleccionadas_" . now()->format('Ymd_His') . ".csv";
                            $headers = [
                                "Content-type"        => "text/csv",
                                "Content-Disposition" => "attachment; filename=$filename",
                                "Pragma"              => "no-cache",
                                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                                "Expires"             => "0"
                            ];

                            $callback = function() use ($records) {
                                $file = fopen('php://output', 'w');
                                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                                fputcsv($file, ['Nro Venta', 'Fecha', 'Cliente', 'Total Bruto', 'Pagado', 'Saldo Pendiente', 'Método Pago', 'Vendedor', 'Estado']);
                                foreach ($records as $record) {
                                    $paymentMethods = $record->payments->pluck('method')->unique()->implode(', ');
                                    fputcsv($file, [
                                        $record->sale_number,
                                        $record->sale_date->format('d/m/Y H:i'),
                                        $record->customer_name,
                                        $record->total,
                                        $record->total_paid,
                                        $record->balance,
                                        strtoupper($paymentMethods ?: 'Crédito'),
                                        $record->creator?->name,
                                        match($record->status) {
                                            'draft' => 'Borrador',
                                            'confirmed' => 'Confirmada',
                                            'cancelled' => 'Cancelada',
                                            default => $record->status
                                        }
                                    ]);
                                }
                                fclose($file);
                            };
                            return response()->stream($callback, 200, $headers);
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_filtered')
                    ->label('Exportar Todo a Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        $filename = "reporte_ventas_" . now()->format('Ymd_His') . ".csv";
                        $headers = [
                            "Content-type"        => "text/csv",
                            "Content-Disposition" => "attachment; filename=$filename",
                            "Pragma"              => "no-cache",
                            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                            "Expires"             => "0"
                        ];

                        $callback = function() use ($records) {
                            $file = fopen('php://output', 'w');
                            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                            fwrite($file, "sep=;\n");
                            fputcsv($file, ['Nro Venta', 'Fecha', 'Cliente', 'Total Bruto', 'Pagado', 'Saldo', 'Vendedor', 'Estado'], ';');
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->sale_number,
                                    $record->sale_date ? $record->sale_date->format('d/m/Y H:i') : '',
                                    $record->customer_name,
                                    $record->total,
                                    $record->total_paid,
                                    $record->balance,
                                    $record->creator?->name,
                                    $record->status
                                ], ';');
                            }
                            fclose($file);
                        };
                        return response()->stream($callback, 200, $headers);
                    }),
                Tables\Actions\Action::make('quick_sale')
                    ->label('Venta Rápida')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->url(fn (): string => SaleResource::getUrl('quick')),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'quick' => SaleResource\Pages\QuickSale::route('/quick'),
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['payments', 'creator']);

        if (!auth()->user()?->hasRole(['super_admin', 'admin'])) {
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
