<?php

namespace App\Filament\Resources\DispatchResource\Pages;

use App\Filament\Resources\DispatchResource;
use App\Services\DispatchService;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class ViewDispatch extends ViewRecord
{
    protected static string $resource = DispatchResource::class;

    protected function getPollInterval(): ?string
    {
        return null; // Desactivado para mayor velocidad. Refrescar manualmente o usar actualizaciones específicas.
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('start')
                ->label('Iniciar Despacho')
                ->icon('heroicon-o-play')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('¿Iniciar despacho?')
                ->modalDescription('Se transferirá el stock de la bodega al camión y se notificará a los pedidos. Asegúrate de tener el GPS activo.')
                ->visible(fn () => $this->record->status === 'pending' && (auth()->user()?->can('dispatches.start') || (auth()->user()?->hasRole('conductor') && $this->record->driver_id === auth()->id())))
                ->action(function (DispatchService $service) {
                    try {
                        $service->start($this->record);
                        Notification::make()->title('Despacho Iniciado')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\Action::make('complete')
                ->label('Marcar Completado')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'in_progress' && auth()->user()?->can('dispatches.complete'))
                ->action(function (DispatchService $service) {
                    $service->complete($this->record);
                    Notification::make()->title('Despacho Completado')->success()->send();
                }),

            Actions\Action::make('deliver')
                ->label('Marcar como Entregado')
                ->icon('heroicon-o-home')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('¿Finalizar Entrega?')
                ->modalDescription('Se generará la factura para cada pedido asociado y se cerrará el despacho.')
                ->visible(fn () => $this->record->status === 'completed' && auth()->user()?->can('dispatches.deliver'))
                ->action(function (DispatchService $service) {
                    $service->deliver($this->record);
                    Notification::make()->title('Entrega Finalizada')->success()->send();
                }),

            Actions\Action::make('recalculate')
                ->label('Recalcular Totales')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->record->recalculateTotals();
                    Notification::make()->title('Totales recalculados con éxito')->success()->send();
                }),

            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Estatus del Despacho')
                    ->visible(fn () => !auth()->user()?->hasRole('conductor'))
                    ->schema([
                        Components\ViewEntry::make('tracker')
                            ->view('components.dispatch-tracker')
                            ->state(fn ($record) => $record->status)
                            ->columnSpanFull(),
                    ]),

                // MAPA DE SEGUIMIENTO EN TIEMPO REAL (Solo visible en progreso para admins)
                Components\Section::make('Mapa de Seguimiento en Tiempo Real')
                    ->visible(fn ($record) => $record->status === 'in_progress' && !auth()->user()?->hasRole('conductor'))
                    ->collapsible()
                    ->schema([
                        Components\ViewEntry::make('map')
                            ->view('components.leaflet-route-map')
                            ->viewData([
                                'locations' => $this->record->locations()->orderBy('created_at', 'asc')->get(),
                                'dispatchId' => $this->record->id,
                                'dispatchNumber' => $this->record->dispatch_number,
                                'driverName' => $this->record->driver?->name ?? $this->record->driver_name ?? 'Sin asignar',
                                'truckName' => $this->record->truck?->name ?? 'Sin asignar',
                                'routeName' => $this->record->route ?? 'Sin ruta',
                                'dispatchStatus' => $this->record->status,
                            ])
                            ->columnSpanFull(),
                    ]),

                Components\Grid::make(3)
                    ->schema([
                        Components\Section::make('Info del Camión')
                            ->columnSpan(1)
                            ->schema([
                                Components\TextEntry::make('truck.name')
                                    ->label('Camión')
                                    ->weight('bold'),
                                Components\TextEntry::make('truck.plate')
                                    ->label('Placa'),
                                Components\TextEntry::make('driver_name')
                                    ->label('Piloto'),
                            ]),
                        Components\Section::make('Ruta y Estado')
                            ->columnSpan(1)
                            ->schema([
                                Components\TextEntry::make('route')
                                    ->label('Ruta / Destino'),
                                Components\TextEntry::make('dispatch_date')
                                    ->label('Fecha')
                                    ->dateTime('d/m/Y H:i'),
                                Components\TextEntry::make('status')
                                    ->label('Estado Actual')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'pending' => 'Pendiente',
                                        'in_progress' => 'En Proceso',
                                        'completed' => 'Completado',
                                        'delivered' => 'Entregado',
                                        default => $state
                                    })
                                    ->color(fn ($state) => match($state) {
                                        'pending' => 'gray',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'delivered' => 'primary',
                                        default => 'gray'
                                    }),
                            ]),
                        Components\Section::make('Carga Totales')
                            ->columnSpan(1)
                            ->schema([
                                Components\TextEntry::make('total_value')
                                    ->label('Valor en Q')
                                    ->money('GTQ')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('primary')
                                    ->state(fn ($record) => (float)$record->total_value > 0 ? $record->total_value : $record->items()->sum('subtotal')),
                                Components\TextEntry::make('total_products')
                                    ->label('Unidades Totales')
                                    ->state(fn ($record) => $record->total_products > 0 ? $record->total_products : $record->items()->sum('quantity')),
                                Components\TextEntry::make('product_types')
                                    ->label('Tipos de Producto')
                                    ->state(fn ($record) => $record->product_types > 0 ? $record->product_types : $record->items()->count()),
                            ]),
                    ]),

                Components\Section::make('Productos en Camión')
                    ->schema([
                        Components\RepeatableEntry::make('items')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                Components\TextEntry::make('product.name')->label('Producto'),
                                Components\TextEntry::make('quantity')->label('Cantidad'),
                                Components\TextEntry::make('unit_price')->label('Valor Unit.')->money('GTQ'),
                                Components\TextEntry::make('subtotal')->label('Subtotal')->money('GTQ')->weight('bold'),
                            ]),
                    ]),

                Components\Section::make('Pedidos Asociados')
                    ->schema([
                        Components\RepeatableEntry::make('orders')
                            ->label('')
                            ->columns(3)
                            ->schema([
                                Components\TextEntry::make('order_number')->label('Pedido')->badge(),
                                Components\TextEntry::make('customer_name')->label('Cliente'),
                                Components\TextEntry::make('total')->label('Monto')->money('GTQ'),
                            ])
                            ->placeholder('No hay pedidos asignados a este despacho.'),
                    ]),
            ]);
    }
}
