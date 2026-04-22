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
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;
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
                    ->columns(2)
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
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_nit')
                            ->label('NIT')
                            ->default('C/F')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Textarea::make('delivery_address')
                            ->label('Dirección de Entrega')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Pago y Estado')
                    ->columns(2)
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
            ->poll('5s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['items', 'creator', 'dispatch']);

        if (auth()->user()?->hasRole('conductor')) {
            $query->where(function (Builder $q) {
                $q->whereHas('dispatch', function (Builder $dq) {
                    $dq->where('driver_id', auth()->id());
                })->orWhereNull('dispatch_id');
            });
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
