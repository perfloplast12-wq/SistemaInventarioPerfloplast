<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderReturnResource\Pages;
use App\Models\OrderReturn;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderReturnResource extends Resource
{
    protected static ?string $model = OrderReturn::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-left';
    protected static ?string $navigationLabel = 'Devoluciones';
    protected static ?string $modelLabel = 'Devolución / Rechazo';
    protected static ?string $pluralModelLabel = 'Gestión de Devoluciones';
    protected static ?string $navigationGroup = 'Operación';
    protected static ?int $navigationSort = 21;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('order_returns.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Devolución')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('dispatch_id')
                            ->relationship('dispatch', 'dispatch_number')
                            ->disabled()
                            ->label('Despacho Vinculado'),
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'order_number')
                            ->disabled()
                            ->label('Pedido Rechazado'),
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->disabled()
                            ->label('Producto Devolución'),
                        Forms\Components\TextInput::make('quantity')
                            ->disabled()
                            ->label('Cantidad'),
                        Forms\Components\Select::make('driver_id')
                            ->relationship('driver', 'name')
                            ->disabled()
                            ->label('Conductor / Piloto'),
                        Forms\Components\Select::make('truck_id')
                            ->relationship('truck', 'name')
                            ->disabled()
                            ->label('Camión (Origen actual)'),
                        Forms\Components\TextInput::make('reason')
                            ->disabled()
                            ->label('Motivo'),
                        Forms\Components\Select::make('status')
                            ->disabled()
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'returned_to_warehouse' => 'Retornado a Bodega',
                                'reassigned' => 'Gestión Resuelta / Cambiado',
                            ]),
                        Forms\Components\Select::make('resolved_by')
                            ->relationship('resolver', 'name')
                            ->disabled()
                            ->label('Resuelto por'),
                        Forms\Components\Textarea::make('notes')
                            ->disabled()
                            ->columnSpanFull()
                            ->label('Notas'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Piloto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Pedido')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cant.')
                    ->badge()
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2, '.', ','))
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
                    ->searchable()
                    ->words(4)
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Pendiente de Revisión',
                        'returned_to_warehouse' => 'Bodega',
                        'reassigned' => 'Cerrado/Cambio',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'danger',
                        'returned_to_warehouse' => 'success',
                        'reassigned' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reportado El')
                    ->dateTime('d M, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente de Revisión',
                        'returned_to_warehouse' => 'Retornado a Bodega',
                        'reassigned' => 'Gestión Resuelta / Cerrado',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\Action::make('resolve_warehouse')
                    ->label('A Bodega')
                    ->icon('heroicon-o-home')
                    ->color('success')
                    ->visible(fn (OrderReturn $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Select::make('warehouse_id')
                            ->label('¿A qué Bodega ingresarás este producto?')
                            ->options(Warehouse::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Nota Adicional')
                            ->rows(2),
                    ])
                    ->action(function (OrderReturn $record, array $data): void {
                        // Crear el movimiento
                        InventoryMovement::create([
                            'type' => 'transfer',
                            'product_id' => $record->product_id,
                            'from_truck_id' => $record->truck_id,
                            'to_warehouse_id' => $data['warehouse_id'],
                            'quantity' => $record->quantity,
                            'note' => 'Retorno por Devolución de Pedido '.$record->order?->order_number. ' | ' . $data['admin_notes'],
                            'created_by' => auth()->id(),
                            'source_type' => OrderReturn::class,
                            'source_id' => $record->id,
                        ]);

                        $record->update([
                            'status' => 'returned_to_warehouse',
                            'resolved_by' => auth()->id(),
                            'notes' => trim($record->notes . "\n[Resolución a Bodega]: " . $data['admin_notes']),
                        ]);

                        // Actualizar el estado del pedido vinculado
                        $record->order?->update(['status' => 'returned']);

                        // Sincronizar estado del despacho
                        if ($record->dispatch) {
                            static::syncDispatchStatus($record->dispatch);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Producto Retornado al Inventario')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('resolve_other')
                    ->label('Reasignar / Cerrar')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (OrderReturn $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('¿Cómo se resolvió?')
                            ->required()
                            ->helperText('Ej: Se cambió producto a cliente en ruta.'),
                    ])
                    ->action(function (OrderReturn $record, array $data): void {
                        $record->update([
                            'status' => 'reassigned',
                            'resolved_by' => auth()->id(),
                            'notes' => trim($record->notes . "\n[Solución Manual]: " . $data['admin_notes']),
                        ]);

                        // Actualizar el estado del pedido vinculado (como completado pero con novedad)
                        $record->order?->update(['status' => 'completed_with_return']);

                        // Sincronizar estado del despacho
                        if ($record->dispatch) {
                            static::syncDispatchStatus($record->dispatch);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Caso Resuelto')
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

    protected static function syncDispatchStatus(\App\Models\Dispatch $dispatch): void
    {
        // 1. Si el despacho ya no está 'in_progress', no hacemos nada
        if ($dispatch->status !== 'in_progress') {
            return;
        }

        // 2. Verificar devoluciones pendientes
        $hasPendingReturns = $dispatch->orderReturns()->where('status', 'pending')->exists();
        if ($hasPendingReturns) {
            return;
        }

        // 3. Verificar si hay pedidos que aún no han sido reportados ni completados
        // Un pedido bloquea el cierre si está en 'assigned' y no tiene devoluciones
        $blockingOrders = $dispatch->orders()
            ->where('status', 'assigned')
            ->whereDoesntHave('returns')
            ->exists();

        // 4. Si no hay bloqueos (todos los pedidos o están completados/devueltos o tienen su devolución resuelta)
        if (!$blockingOrders) {
            $hasAnyReturns = $dispatch->orderReturns()->exists();
            $newStatus = $hasAnyReturns ? 'completed' : 'delivered';
            
            $dispatch->update(['status' => $newStatus]);

            \Filament\Notifications\Notification::make()
                ->title('Viaje Finalizado Automáticamente')
                ->body("El despacho {$dispatch->dispatch_number} se ha cerrado porque se resolvieron todas las novedades.")
                ->success()
                ->send();
        }
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
            'index' => Pages\ListOrderReturns::route('/'),
        ];
    }
}
