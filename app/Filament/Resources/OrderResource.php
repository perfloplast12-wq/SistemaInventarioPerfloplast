<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'ÁREA COMERCIAL';
    protected static ?string $modelLabel = 'Pedido';
    protected static ?string $pluralModelLabel = 'Pedidos';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('orders.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return (auth()->user()?->can('orders.edit') ?? false) && $record->status === 'pending';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('orders.delete') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Cliente')
                    ->columns(['default' => 1, 'sm' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Nro. Pedido')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generado'),
                        Forms\Components\DateTimePicker::make('order_date')
                            ->label('Fecha del Pedido')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nombre del Cliente')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[^0-9]+$/')
                            ->validationMessages([
                                'regex' => 'El nombre del cliente no debe contener números.',
                            ]),
                        Forms\Components\TextInput::make('customer_nit')
                            ->label('NIT')
                            ->default('C/F')
                            ->maxLength(20)
                            ->regex('/^(C\/F|CF|[0-9\-Kk]+)$/i')
                            ->validationMessages([
                                'regex' => 'El NIT debe ser C/F o un número de NIT válido.',
                            ]),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->nullable()
                            ->maxLength(8)
                            ->regex('/^[0-9]{8}$/')
                            ->validationMessages([
                                'regex' => 'El teléfono de contacto debe contener exactamente 8 dígitos.',
                            ]),
                        Forms\Components\Textarea::make('delivery_address')
                            ->label('Dirección de Entrega')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Pago y Estado')
                    ->columns(['default' => 1, 'sm' => 2])
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Método de Pago')
                            ->options([
                                'cash' => 'Efectivo',
                                'transfer' => 'Transferencia',
                                'card' => 'Tarjeta',
                                'cod' => 'Contra Entrega (COD)',
                            ])
                            ->required()
                            ->default('cod'),
                        Forms\Components\Select::make('payment_status')
                            ->label('Estado de Pago')
                            ->options([
                                'pending' => 'Pendiente',
                                'partial' => 'Parcial',
                                'paid' => 'Pagado',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas / Observaciones')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Productos del Pedido')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->columns(['default' => 1, 'sm' => 2, 'md' => 4])
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto')
                                    ->options(Product::where('type', 'finished_product')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $price = Product::find($state)?->sale_price ?? 0;
                                        $set('unit_price', number_format((float) $price, 2, '.', ''));
                                        $set('color_id', null);
                                    }),
                                Forms\Components\Select::make('color_id')
                                    ->label('Color')
                                    ->relationship('color', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->reactive()
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                    ->afterStateUpdated(fn ($state, $get, Forms\Set $set) => 
                                        $set('subtotal', (float)$state * (float)$get('unit_price'))),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Precio Unitario')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Q')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', '')),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                    ->prefix('Q'),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => 
                                ($state['product_id'] ?? null) 
                                ? Product::find($state['product_id'])?->name . 
                                  (($state['color_id'] ?? null) ? ' (' . \App\Models\Color::find($state['color_id'])?->name . ')' : '') .
                                  ' (' . ($state['quantity'] ?? 0) . ')' 
                                : null),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información del Pedido')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('order_number')
                            ->label('Nro. Pedido')
                            ->badge()
                            ->color('info')
                            ->weight('bold'),
                        Components\TextEntry::make('order_date')
                            ->label('Fecha del Pedido')
                            ->dateTime('d/m/Y H:i'),
                        Components\TextEntry::make('status')
                            ->label('Estado Actual')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'pending' => 'Pendiente',
                                'shipped' => 'Despachado',
                                'completed' => 'Entregado',
                                'cancelled' => 'Cancelado',
                                'returned' => 'Devuelto',
                                'completed_with_return' => 'Cerrado con Novedad',
                                default => $state
                            })
                            ->color(fn ($state) => match($state) {
                                'pending' => 'gray',
                                'shipped' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'returned' => 'warning',
                                'completed_with_return' => 'warning',
                                default => 'gray'
                            }),
                        Components\TextEntry::make('customer_name')
                            ->label('Cliente'),
                        Components\TextEntry::make('customer_nit')
                            ->label('NIT'),
                        Components\TextEntry::make('phone')
                            ->label('Teléfono de Contacto')
                            ->placeholder('N/A'),
                        Components\TextEntry::make('delivery_address')
                            ->label('Dirección de Entrega')
                            ->columnSpanFull()
                            ->icon('heroicon-o-map-pin'),
                    ]),

                Components\Section::make('Ubicación Geográfica de la Pre-venta')
                    ->collapsible()
                    ->schema([
                        Components\ViewEntry::make('map_location')
                            ->view('components.order-delivery-map')
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Productos del Pedido')
                    ->schema([
                        Components\RepeatableEntry::make('items')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                Components\TextEntry::make('product.name')
                                    ->label('Producto')
                                    ->weight('bold'),
                                Components\TextEntry::make('color.display_name')
                                    ->label('Color')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return 'N/A';
                                        if (str_contains($state, ' (')) {
                                            $state = explode(' (', $state)[0];
                                        }
                                        return ucfirst($state);
                                    })
                                    ->placeholder('N/A'),
                                Components\TextEntry::make('quantity')
                                    ->label('Cantidad')
                                    ->formatStateUsing(fn ($state) => number_format($state, (round($state) == $state ? 0 : 2), '.', ',')),
                                Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->formatStateUsing(fn ($state) => 'Q ' . number_format($state, 2, '.', ','))
                                    ->weight('bold'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Nro. Pedido')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('origin')
                    ->label('Origen')
                    ->state(fn ($record) => $record->sale_id ? "Preventa #{$record->sale->sale_number}" : 'Pedido Directo')
                    ->description(fn ($record) => $record->sale_id ? "Sincronizado" : "Manual")
                    ->badge()
                    ->color(fn ($record) => $record->sale_id ? 'success' : 'gray')
                    ->icon(fn ($record) => $record->sale_id ? 'heroicon-m-arrow-path' : 'heroicon-m-user'),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Pago')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'cash' => 'Efectivo',
                        'transfer' => 'Transferencia',
                        'card' => 'Tarjeta',
                        'cod' => 'Contrapago',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'cod' => 'warning',
                        'cash' => 'success',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Pendiente',
                        'assigned' => 'Asignado',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'returned' => 'Devolución',
                        'completed_with_return' => 'Cerrado con Novedad',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'gray',
                        'assigned' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'returned' => 'danger',
                        'completed_with_return' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ','))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'assigned' => 'Asignado',
                        'completed' => 'Completado',
                        'returned' => 'Devolución',
                        'completed_with_return' => 'Cerrado con Novedad',
                    ]),
                Tables\Filters\Filter::make('order_date')
                    ->form([
                        \Filament\Forms\Components\Select::make('period')
                            ->label('Período')
                            ->options([
                                'today' => 'Hoy',
                                'this_week' => 'Esta Semana',
                                'this_month' => 'Este Mes',
                                'last_month' => 'Mes Pasado',
                                'custom' => 'Personalizado',
                            ])
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, \Filament\Forms\Set $set) {
                                if (!$state) return;
                                $now = \Illuminate\Support\Carbon::now();
                                switch ($state) {
                                    case 'today':
                                        $set('from', $now->toDateString());
                                        $set('until', $now->toDateString());
                                        break;
                                    case 'this_week':
                                        $set('from', $now->startOfWeek()->toDateString());
                                        $set('until', $now->endOfWeek()->toDateString());
                                        break;
                                    case 'this_month':
                                        $set('from', $now->startOfMonth()->toDateString());
                                        $set('until', $now->endOfMonth()->toDateString());
                                        break;
                                    case 'last_month':
                                        $prevMonth = \Illuminate\Support\Carbon::now()->subMonth();
                                        $set('from', $prevMonth->startOfMonth()->toDateString());
                                        $set('until', $prevMonth->endOfMonth()->toDateString());
                                        break;
                                }
                            }),
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Forms\Set $set) => $set('period', 'custom')),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Forms\Set $set) => $set('period', 'custom')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Desde ' . \Illuminate\Support\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Hasta ' . \Illuminate\Support\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(fn ($livewire) => \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\OrdersExport($livewire->getFilteredTableQuery()->get()),
                        'pedidos_' . now()->format('Y-m-d_H-i') . '.xlsx'
                    )),
            ])
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['items', 'creator', 'dispatch', 'sale']);
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Si es conductor, solo ve los pedidos asignados a sus propios despachos
        if ($user->hasRole('conductor')) {
            $query->whereHas('dispatch', function (Builder $dq) use ($user) {
                $dq->where('driver_id', $user->id);
            });
        }

        // Si es vendedor, solo ve los pedidos creados por él
        if ($user->hasRole('sales')) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
