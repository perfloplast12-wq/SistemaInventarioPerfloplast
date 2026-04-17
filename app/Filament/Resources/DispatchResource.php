<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DispatchResource\Pages;
use App\Models\Dispatch;
use App\Models\Order;
use App\Models\Truck;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\OrderReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DispatchResource extends Resource
{
    protected static ?string $model = Dispatch::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Despacho';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Despacho';
    protected static ?string $pluralModelLabel = 'Despachos';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('dispatches.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('dispatches.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return (auth()->user()?->can('dispatches.edit') ?? false) && $record->status === 'pending';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('dispatches.delete') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Info del Despacho')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('dispatch_number')
                            ->label('Nro. Despacho')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generado'),
                        Forms\Components\DateTimePicker::make('dispatch_date')
                            ->label('Fecha')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('truck_id')
                            ->label('Camión')
                            ->options(Truck::isActive()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $truck = Truck::find($state);
                                if ($truck) {
                                    $set('driver_id', $truck->driver_id);
                                    $set('driver_name', $truck->driver_name);
                                }
                            }),
                        Forms\Components\Select::make('driver_id')
                            ->label('Piloto / Conductor')
                            ->relationship('driver', 'name', fn ($query) => $query->role('conductor'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('driver_name')
                            ->label('Nombre Auxiliar (Opcional)')
                            ->helperText('Nombre del piloto si no es un usuario del sistema.'),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Bodega Origen')
                            ->options(Warehouse::pluck('name', 'id'))
                            ->required(),
                        Forms\Components\TextInput::make('route')
                            ->label('Ruta / Destino')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Pedidos Asociados')
                    ->schema([
                        Forms\Components\Select::make('orders')
                            ->label('Seleccionar Pedidos Pendientes')
                            ->relationship('orders', 'order_number', fn ($query) => $query->where('status', 'pending')->orWhere('dispatch_id', $form->getRecord()?->id))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $old, Forms\Set $set, Forms\Get $get) {
                                if (empty($state)) {
                                    $set('items', []);
                                    return;
                                }
                                
                                // Determinar qué IDs son nuevos comparando con el estado anterior
                                $selectedOrderIds = (array)$state;
                                $currentItems = []; // Reiniciamos para reconstruir basándonos solo en los pedidos seleccionados
                                
                                foreach ($selectedOrderIds as $orderId) {
                                    $order = Order::with('items')->find($orderId);
                                    if (!$order) continue;

                                    foreach ($order->items as $orderItem) {
                                        $found = false;
                                        foreach ($currentItems as &$item) {
                                            if ($item['product_id'] == $orderItem->product_id && ($item['color_id'] ?? null) == $orderItem->color_id) {
                                                // Sumar cantidades con precisión
                                                $item['quantity'] = (float)number_format((float)$item['quantity'] + (float)$orderItem->quantity, 3, '.', '');
                                                $item['subtotal'] = (float)number_format((float)$item['quantity'] * (float)$item['unit_price'], 2, '.', '');
                                                $found = true;
                                                break;
                                            }
                                        }

                                        if (!$found) {
                                            $currentItems[] = [
                                                'product_id' => $orderItem->product_id,
                                                'color_id' => $orderItem->color_id,
                                                'quantity' => (float)number_format((float)$orderItem->quantity, 2, '.', ''),
                                                'unit_price' => (float)number_format((float)$orderItem->unit_price, 2, '.', ''),
                                                'subtotal' => (float)number_format((float)$orderItem->subtotal, 2, '.', ''),
                                            ];
                                        }
                                    }
                                }

                                $set('items', $currentItems);
                                
                                // Forzar el recalculo de los placeholders de totales
                                $set('total_value_display', 'Q ' . number_format(collect($currentItems)->sum('subtotal'), 2));
                            }),
                    ]),

                Forms\Components\Section::make('Carga Totales')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Placeholder::make('total_value_display')
                            ->label('Valor Total (Q)')
                            ->content(fn (Forms\Get $get) => 'Q ' . number_format(round(collect($get('items'))->sum('subtotal'), 2), 2))
                            ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),
                        Forms\Components\Placeholder::make('total_products_display')
                            ->label('Unidades Totales')
                            ->content(fn (Forms\Get $get) => round(collect($get('items'))->sum('quantity'), 2)),
                        Forms\Components\Placeholder::make('product_types_display')
                            ->label('Tipos de Producto')
                            ->content(fn (Forms\Get $get) => collect($get('items'))->unique('product_id')->count()),
                    ]),

                Forms\Components\Section::make('Productos Cargados')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->live()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto')
                                    ->options(Product::where('type', 'finished_product')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $set('unit_price', Product::find($state)?->sale_price ?? 0);
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
                                    ->step(0.01)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $get, Forms\Set $set) => 
                                        $set('subtotal', (float)number_format((float)$state * (float)$get('unit_price'), 2, '.', ''))),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Valor Unitario (Q)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required()
                                    ->prefix('Q')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $get, Forms\Set $set) => 
                                        $set('subtotal', (float)number_format((float)$state * (float)$get('quantity'), 2, '.', ''))),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal (Q)')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
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

                Forms\Components\Textarea::make('notes')
                    ->label('Notas / Observaciones')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')
                    ->label('Nro. Despacho')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('truck.name')
                    ->label('Camión')
                    ->searchable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Piloto (Usuario)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('driver_name')
                    ->label('Piloto (Texto)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('route')
                    ->label('Ruta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Valor Carga')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Proceso',
                        'completed' => 'Completado',
                        'delivered' => 'Entregado',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'delivered' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('order_returns_count')
                    ->label('Alertas/Dev.')
                    ->counts('orderReturns')
                    ->badge()
                    ->color(fn ($record) => 
                        $record->orderReturns()->where('status', 'pending')->exists() 
                            ? 'danger' 
                            : ($record->orderReturns()->exists() ? 'success' : 'gray')
                    )
                    ->formatStateUsing(fn ($state, $record) => $state > 0 ? "{$state} Devolución(es)" : 'Ninguna')
                    ->description(fn ($record) => 
                        $record->orderReturns()->where('status', 'pending')->exists() 
                            ? 'Pendiente revisión' 
                            : ($record->orderReturns()->exists() ? 'Resueltas' : '')
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Proceso',
                        'completed' => 'Completado',
                        'delivered' => 'Entregado',
                    ]),
                
                Tables\Filters\SelectFilter::make('driver_id')
                    ->label('Piloto (Usuario)')
                    ->relationship('driver', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('driver_name')
                    ->label('Piloto (Texto)')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label('Nombre del Piloto'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $v) => $q->where('driver_name', 'like', "%$v%"))),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('view_map')
                    ->label('Ver Ruta')
                    ->icon('heroicon-o-map')
                    ->color('success')
                    ->visible(fn ($record) => $record->locations()->exists())
                    ->modalHeading(fn ($record) => 'Historial de Ruta: ' . $record->dispatch_number)
                    ->modalSubmitAction(false) // Solo cerrar
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('6xl')
                    ->modalContent(fn (Dispatch $record) => view('components.leaflet-route-map', [
                        'locations' => $record->locations()->orderBy('created_at', 'asc')->get(),
                        'dispatchId' => $record->id
                    ])),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
                Tables\Actions\Action::make('report_return')
                    ->label('Reportar Devolución')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->form(function (Dispatch $record) {
                        return [
                            Forms\Components\Select::make('order_id')
                                ->label('Seleccione el Pedido')
                                ->options($record->orders->pluck('order_number', 'id'))
                                ->required()
                                ->live(),
                            Forms\Components\Select::make('product_id')
                                ->label('Producto Rechazado/Devuelto')
                                ->options(function (Forms\Get $get) {
                                    $orderId = $get('order_id');
                                    if (!$orderId) return [];
                                    $order = \App\Models\Order::with('items.product', 'items.color')->find($orderId);
                                    if (!$order) return [];
                                    return $order->items->mapWithKeys(function ($item) {
                                        $name = $item->product->name . ($item->color ? " ({$item->color->name})" : "") . ' [Max: ' . $item->quantity . ']';
                                        // Usamos un key compuesto para pasar el color_id
                                        $key = $item->product_id . '|' . ($item->color_id ?? '');
                                        return [$key => $name];
                                    })->toArray();
                                })
                                ->required()
                                ->live(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad Devuelta')
                                ->numeric()
                                ->step(0.01)
                                ->required()
                                ->minValue(0.01),
                            Forms\Components\Select::make('reason')
                                ->label('Motivo del Rechazo')
                                ->options([
                                    'Producto Dañado' => 'Producto Dañado / Mal estado',
                                    'Empaque Roto' => 'Empaque Roto',
                                    'Equivocación de Pedido' => 'Equivocación de Pedido',
                                    'Cliente no aceptó' => 'Cliente no aceptó / Canceló en puerta',
                                    'Otro' => 'Otro (Especificar en notas)',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notas Adicionales')
                                ->rows(2),
                        ];
                    })
                    ->action(function (array $data, Dispatch $record): void {
                        list($productId, $colorId) = explode('|', $data['product_id']);
                        $colorId = $colorId === '' ? null : $colorId;

                        OrderReturn::create([
                            'dispatch_id' => $record->id,
                            'order_id' => $data['order_id'],
                            'product_id' => $productId,
                            'color_id' => $colorId,
                            'driver_id' => $record->driver_id ?: auth()->id(),
                            'truck_id' => $record->truck_id,
                            'quantity' => $data['quantity'],
                            'reason' => $data['reason'],
                            'status' => 'pending',
                            'notes' => $data['notes'],
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Devolución Reportada')
                            ->body('La alerta ha sido enviada al administrador.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('complete_dispatch')
                    ->label('Finalizar Entrega')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Finalizar el viaje de despacho?')
                    ->modalDescription('Esto marcará el despacho como finalizado. Si hay devoluciones pendientes, por favor resuélvalas primero en el módulo de Inventario.')
                    ->modalSubmitActionLabel('Sí, finalizar')
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->action(function (Dispatch $record): void {
                        $hasPendingReturns = $record->orderReturns()->where('status', 'pending')->exists();
                        
                        if ($hasPendingReturns) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede finalizar')
                                ->body('Existen devoluciones pendientes de revisión para este despacho. Por favor, procéselas en Inventario > Devoluciones.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $hasAnyReturns = $record->orderReturns()->exists();
                        $newStatus = $hasAnyReturns ? 'completed' : 'delivered';

                        $record->update(['status' => $newStatus]);

                        \Filament\Notifications\Notification::make()
                            ->title($hasAnyReturns ? 'Despacho Completado con Novedades' : 'Despacho Entregado Satisfactoriamente')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('conductor')) {
            $query->where('driver_id', auth()->id());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispatches::route('/'),
            'create' => Pages\CreateDispatch::route('/create'),
            'view' => Pages\ViewDispatch::route('/{record}'),
            'edit' => Pages\EditDispatch::route('/{record}/edit'),
        ];
    }
}
