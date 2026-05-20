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

    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'LOGÍSTICA';
    protected static ?string $modelLabel = 'Despacho';
    protected static ?string $pluralModelLabel = 'Despachos';
    protected static bool $shouldRegisterNavigation = false;

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
                            ->label('Zona / Ruta General')
                            ->helperText('Zona general del recorrido del piloto (ej: Zona 12 - Cobán).')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Pedidos Asociados')
                    ->schema([
                        Forms\Components\Select::make('selected_orders')
                            ->label('Seleccionar Pedidos Pendientes')
                            ->options(function ($livewire) {
                                $query = Order::where('status', 'pending');
                                if (method_exists($livewire, 'getRecord') && $livewire->getRecord()) {
                                    $query->orWhere('dispatch_id', $livewire->getRecord()->id);
                                }
                                return $query->get()->mapWithKeys(fn ($o) => [$o->id => "{$o->order_number} — {$o->customer_name}"]);
                            })
                            ->dehydrated(false)
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
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
                                // Autofill bodega si está vacío
                                if (!empty($selectedOrderIds)) {
                                    $firstOrderId = reset($selectedOrderIds);
                                    $firstOrder = Order::with('sale')->find($firstOrderId);
                                    if ($firstOrder) {
                                        if (empty($get('warehouse_id')) && $firstOrder->sale && $firstOrder->sale->from_warehouse_id) {
                                            $set('warehouse_id', $firstOrder->sale->from_warehouse_id);
                                        }
                                    }
                                }

                                $set('items', $currentItems);
                            }),
                        Forms\Components\Placeholder::make('selected_orders_summary')
                            ->label('Detalle de Entrega por Pedido')
                            ->content(function (Forms\Get $get) {
                                $orderIds = (array) $get('selected_orders');
                                if (empty($orderIds)) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500 italic">No hay pedidos seleccionados.</p>');
                                }

                                $orders = Order::whereIn('id', $orderIds)->get();
                                $html = '<div class="overflow-x-auto my-2"><table class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b bg-gray-100 dark:bg-gray-800 font-semibold">
                                            <th class="p-2">Pedido</th>
                                            <th class="p-2">Cliente</th>
                                            <th class="p-2">Dirección de Entrega</th>
                                            <th class="p-2 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">';
                                foreach ($orders as $o) {
                                    $total = number_format((float) $o->total, 2);
                                    $addr = htmlspecialchars((string) $o->delivery_address);
                                    $name = htmlspecialchars((string) $o->customer_name);
                                    $html .= "<tr>
                                        <td class='p-2 font-mono'>{$o->order_number}</td>
                                        <td class='p-2 font-medium'>{$name}</td>
                                        <td class='p-2 text-gray-600 dark:text-gray-300'>{$addr}</td>
                                        <td class='p-2 text-right font-semibold'>Q {$total}</td>
                                    </tr>";
                                }
                                $html .= '</tbody></table></div>';
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ]),

                Forms\Components\Section::make('Carga Totales')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Placeholder::make('total_value_display')
                            ->label('Valor Total (Q)')
                            ->content(fn (Forms\Get $get) => 'Q ' . number_format(round(collect($get('items'))->sum('subtotal'), 2), 2, '.', ','))
                            ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),
                        Forms\Components\Placeholder::make('total_products_display')
                            ->label('Unidades Totales')
                            ->content(function (Forms\Get $get) {
                                $total = (float)collect($get('items'))->sum('quantity');
                                return number_format($total, (round($total) == $total ? 0 : 2), '.', ',');
                            }),
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
                                    ->readOnly()
                                    ->prefix('Q')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $get, Forms\Set $set) => 
                                        $set('subtotal', (float)number_format((float)$state * (float)$get('quantity'), 2, '.', ''))),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal (Q)')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                    ->prefix('Q'),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => 
                                ($state['product_id'] ?? null) 
                                ? Product::find($state['product_id'])?->name . 
                                  (($state['color_id'] ?? null) ? ' (' . \App\Models\Color::find($state['color_id'])?->name . ')' : '') .
                                  ' (' . number_format((float)($state['quantity'] ?? 0), (round($state['quantity'] ?? 0) == ($state['quantity'] ?? 0) ? 0 : 2), '.', ',') . ')' 
                                : null),
                    ]),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas / Observaciones')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Info del Despacho')
                    ->columns(3)
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('dispatch_number')->label('Nro. Despacho')->badge()->color('info'),
                        \Filament\Infolists\Components\TextEntry::make('dispatch_date')->label('Fecha')->dateTime('d/m/Y H:i'),
                        \Filament\Infolists\Components\TextEntry::make('status')->label('Estado Actual')->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'pending' => 'Pendiente',
                                'in_progress' => 'En Proceso',
                                'completed' => 'Completado',
                                'delivered' => 'Entregado',
                                default => $state,
                            })
                            ->color(fn ($state) => match ($state) {
                                'pending' => 'gray',
                                'in_progress' => 'info',
                                'completed' => 'success',
                                'delivered' => 'primary',
                                default => 'gray',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('truck.name')->label('Camión'),
                        \Filament\Infolists\Components\TextEntry::make('driver.name')->label('Piloto'),
                        \Filament\Infolists\Components\TextEntry::make('route')->label('Ruta / Destino'),
                    ]),

                \Filament\Infolists\Components\Section::make('Productos en Camión')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                \Filament\Infolists\Components\Grid::make(4)
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('product.name')->label('Producto')->weight('bold'),
                                        \Filament\Infolists\Components\TextEntry::make('color.name')->label('Color')->placeholder('N/A'),
                                        \Filament\Infolists\Components\TextEntry::make('quantity')
                                            ->label('Cantidad')
                                            ->formatStateUsing(fn ($state) => number_format($state, (round($state) == $state ? 0 : 2), '.', ','))
                                            ->badge()
                                            ->color('success'),
                                        \Filament\Infolists\Components\TextEntry::make('subtotal')
                                            ->label('Subtotal')
                                            ->money('GTQ'),
                                    ]),
                            ])->columns(1),
                    ]),

                \Filament\Infolists\Components\Section::make('Novedades y Devoluciones')
                    ->description('Historial de devoluciones y su estado de resolución por parte de administración.')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('orderReturns')
                            ->label('')
                            ->schema([
                                \Filament\Infolists\Components\Grid::make(4)
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('product.name')->label('Producto')->weight('bold'),
                                        \Filament\Infolists\Components\TextEntry::make('quantity')
                                            ->label('Cantidad Devuelta')
                                            ->badge()
                                            ->color('danger'),
                                        \Filament\Infolists\Components\TextEntry::make('status')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => match ($state) {
                                                'pending' => 'Pendiente Admin',
                                                'returned_to_warehouse' => 'Devuelto a Bodega',
                                                'reassigned' => 'Solucionado / Reasignado',
                                                default => $state,
                                            })
                                            ->color(fn ($state) => match ($state) {
                                                'pending' => 'warning',
                                                'returned_to_warehouse' => 'success',
                                                'reassigned' => 'info',
                                                default => 'gray',
                                            }),
                                        \Filament\Infolists\Components\TextEntry::make('resolver.name')
                                            ->label('Resuelto Por')
                                            ->placeholder('Aún no resuelto'),
                                        \Filament\Infolists\Components\TextEntry::make('notes')
                                            ->label('Notas de Administración')
                                            ->columnSpanFull()
                                            ->placeholder('Sin notas'),
                                    ]),
                            ])->columns(1),
                    ])->visible(fn ($record) => $record->orderReturns()->exists()),
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
                    ->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ','))
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
                    ->badge()
                    ->color(fn ($record) => 
                        $record->order_returns_count > 0 
                            ? ($record->orderReturns->where('status', 'pending')->count() > 0 ? 'danger' : 'success')
                            : 'gray'
                    )
                    ->formatStateUsing(fn ($state, $record) => $record->order_returns_count > 0 ? "{$record->order_returns_count} Devolución(es)" : 'Ninguna')
                    ->description(fn ($record) => 
                        $record->order_returns_count > 0 
                            ? ($record->orderReturns->where('status', 'pending')->count() > 0 ? 'Pendiente revisión' : 'Resueltas') 
                            : ''
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
                                 fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '>=', $date),
                             )
                             ->when(
                                 $data['until'],
                                 fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '<=', $date),
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
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Ver Despacho')
                    ->iconButton(),

                Tables\Actions\Action::make('view_map')
                    ->label('')
                    ->tooltip('Ver Ruta en Mapa')
                    ->icon('heroicon-o-map')
                    ->color('success')
                    ->iconButton()
                    ->url(fn (Dispatch $record) => \App\Filament\Pages\RealTimeRoutesDashboard::getUrl([
                        'dispatch' => $record->id,
                    ]))
                    ->visible(fn ($record) => $record->status === 'in_progress' && !auth()->user()?->hasRole('conductor'))
                    ->modalHeading(fn ($record) => 'Ruta Diaria del Camión: ' . ($record->truck?->name ?? 'Sin asignar'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('6xl')
                    ->modalContent(function (Dispatch $record) {
                        $activeDispatchIds = [];
                        if ($record->truck_id) {
                            $activeDispatchIds = Dispatch::where('truck_id', $record->truck_id)
                                ->where('status', 'in_progress')
                                ->pluck('id')
                                ->toArray();
                        }
                        if (!in_array($record->id, $activeDispatchIds)) {
                            $activeDispatchIds[] = $record->id;
                        }
                        $latest = null;
                        foreach ($activeDispatchIds as $aid) {
                            $loc = \App\Models\DispatchLocation::where('dispatch_id', $aid)
                                ->orderByDesc('created_at')
                                ->first();
                            if ($loc) {
                                if (!$latest || $loc->created_at->gt($latest->created_at)) {
                                    $latest = $loc;
                                }
                            }
                        }
                        if (!$latest && $record->truck_id) {
                            $otherIds = Dispatch::where('truck_id', $record->truck_id)
                                ->whereNotIn('id', $activeDispatchIds)
                                ->orderByDesc('id')
                                ->limit(5)
                                ->pluck('id');
                            foreach ($otherIds as $oid) {
                                $loc = \App\Models\DispatchLocation::where('dispatch_id', $oid)
                                    ->orderByDesc('created_at')
                                    ->first();
                                if ($loc) {
                                    if (!$latest || $loc->created_at->gt($latest->created_at)) {
                                        $latest = $loc;
                                    }
                                }
                            }
                        }
                        $locations = $latest ? collect([$latest]) : collect();
                        return view('components.leaflet-route-map', [
                            'record'          => $record,
                            'locations'       => $locations,
                            'dispatchId'      => $record->id,
                            'dispatchNumber'  => $record->dispatch_number,
                            'driverName'      => $record->driver?->name ?? $record->driver_name ?? 'Sin asignar',
                            'truckName'       => $record->truck?->name ?? 'Sin asignar',
                            'routeName'       => $record->route ?? 'Sin ruta',
                            'dispatchStatus'  => $record->status,
                            'hideOrders'      => true,
                        ]);
                    }),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->iconButton()
                    ->visible(fn ($record) => $record->status === 'pending'),

                // Grupo compacto de acciones para despachos En Proceso
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('report_return')
                        ->label('Reportar Devolución')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'in_progress')
                        ->form(function (Dispatch $record) {
                            return [
                                Forms\Components\Select::make('order_id')
                                    ->label('Seleccione el Pedido')
                                    ->options($record->orders->pluck('order_number', 'id'))
                                    ->required()
                                    ->live(),
                                Forms\Components\Repeater::make('return_items')
                                    ->label('Productos Devueltos')
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Producto')
                                            ->options(function (Forms\Get $get) {
                                                $orderId = $get('../../order_id');
                                                if (!$orderId) return [];
                                                $order = \App\Models\Order::with('items.product', 'items.color')->find($orderId);
                                                if (!$order) return [];
                                                return $order->items->mapWithKeys(function ($item) {
                                                    $qtyFormatted = number_format($item->quantity, (round($item->quantity) == $item->quantity ? 0 : 2), '.', ',');
                                                    $name = $item->product->name . ($item->color ? " ({$item->color->name})" : "") . " [Max: {$qtyFormatted}]";
                                                    $key = $item->product_id . '|' . ($item->color_id ?? '');
                                                    return [$key => $name];
                                                })->toArray();
                                            })
                                            ->required()
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->step(0.01)
                                            ->required()
                                            ->minValue(0.01)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(1)
                                    ->addActionLabel('Añadir otro producto'),
                                Forms\Components\Select::make('reason')
                                    ->label('Motivo del Rechazo')
                                    ->options([
                                        'Producto Dañado'          => 'Producto Dañado / Mal estado',
                                        'Empaque Roto'             => 'Empaque Roto',
                                        'Equivocación de Pedido'   => 'Equivocación de Pedido',
                                        'Cliente no aceptó'        => 'Cliente no aceptó / Canceló en puerta',
                                        'Otro'                     => 'Otro (Especificar en notas)',
                                    ])
                                    ->required(),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas Adicionales')
                                    ->rows(2),
                            ];
                        })
                        ->action(function (array $data, Dispatch $record): void {
                            foreach ($data['return_items'] as $item) {
                                list($productId, $colorId) = explode('|', $item['product_id']);
                                $colorId = $colorId === '' ? null : $colorId;
                                OrderReturn::create([
                                    'dispatch_id' => $record->id,
                                    'order_id'    => $data['order_id'],
                                    'product_id'  => $productId,
                                    'color_id'    => $colorId,
                                    'driver_id'   => $record->driver_id ?: auth()->id(),
                                    'truck_id'    => $record->truck_id,
                                    'quantity'    => $item['quantity'],
                                    'reason'      => $data['reason'],
                                    'status'      => 'pending',
                                    'notes'       => $data['notes'] ?? null,
                                ]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Devolución(es) Reportada(s)')
                                ->body('Las alertas han sido enviadas al administrador.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('cancel_dispatch')
                        ->label('Cancelar Despacho')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('¿Cancelar Despacho?')
                        ->modalDescription('Esto eliminará el despacho y devolverá los productos a la bodega de origen. Los pedidos volverán a estado pendiente.')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'in_progress']) && auth()->user()?->can('dispatches.delete'))
                        ->action(function (Dispatch $record, \App\Services\DispatchService $service): void {
                            try {
                                $service->cancel($record);
                                \Filament\Notifications\Notification::make()
                                    ->title('Despacho Cancelado')
                                    ->body('El stock ha sido devuelto a la bodega con éxito.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('complete_dispatch')
                        ->label('Finalizar Entrega')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('¿Finalizar el viaje de despacho?')
                        ->modalDescription('Esto marcará el despacho como finalizado. Si hay devoluciones pendientes, por favor resuélvalas primero en el módulo de Inventario.')
                        ->modalSubmitActionLabel('Sí, finalizar')
                        ->visible(fn ($record) => $record->status === 'in_progress' && !$record->orderReturns()->where('status', 'pending')->exists())
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
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Más acciones')
                ->color('gray')
                ->button()
                ->size('sm')
                ->visible(fn ($record) => $record->status === 'in_progress'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\DispatchExport($records), 
                            "Despachos_Perfloplast_" . now()->format('Ymd_His') . ".xlsx"
                        );
                    }),
            ])
            ->poll('120s')
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
        $query = parent::getEloquentQuery()
            ->with(['truck', 'driver', 'orderReturns'])
            ->withCount('orderReturns');

        $user = auth()->user();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Si es conductor, solo ve los despachos asignados a él
        if ($user->hasRole('conductor')) {
            $query->where('driver_id', $user->id);
        }

        // Si es vendedor, solo ve los despachos que contienen pedidos creados por él
        if ($user->hasRole('sales')) {
            $query->whereHas('orders', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
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
