<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    public function getTitle(): string
    {
        return 'Venta: ' . $this->record->sale_number;
    }

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirmar Venta')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('¿Confirmar esta venta?')
                ->modalDescription('Se descontará el stock y la venta quedará como confirmada. Esta acción no se puede deshacer fácilmente.')
                ->modalSubmitActionLabel('Sí, Confirmar')
                ->visible(fn () => $this->record->status === 'draft')
                ->action(function (SaleService $service) {
                    try {
                        $service->confirm($this->record);
                        Notification::make()
                            ->title('✓ Venta Confirmada')
                            ->body('Stock descontado y venta registrada exitosamente.')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al confirmar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\EditAction::make()
                ->label('Editar')
                ->color('gray')
                ->visible(fn () => $this->record->status === 'draft'),

            Actions\Action::make('print_invoice')
                ->label('Imprimir Factura')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->visible(fn () => $this->record->status === 'confirmed')
                ->url(fn () => route('invoices.print', ['invoice' => \App\Models\Invoice::where('sale_id', $this->record->id)->first()]))
                ->openUrlInNewTab(),

            Actions\Action::make('back_to_list')
                ->label('Volver a Ventas')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => $this->getResource()::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información General')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('sale_number')
                            ->label('Nro. Venta')
                            ->badge()
                            ->color('info')
                            ->weight('bold'),

                        Components\TextEntry::make('sale_date')
                            ->label('Fecha')
                            ->dateTime('d/m/Y H:i'),

                        Components\TextEntry::make('customer_name')
                            ->label('Cliente'),

                        Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'draft' => 'Borrador',
                                'confirmed' => 'Confirmada',
                                'cancelled' => 'Cancelada',
                                default => $state,
                            })
                            ->color(fn (string $state) => match ($state) {
                                'draft' => 'gray',
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('fromWarehouse.name')
                            ->label('Bodega Origen')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->from_warehouse_id),

                        Components\TextEntry::make('fromTruck.name')
                            ->label('Camión Origen')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->from_truck_id),

                        Components\TextEntry::make('note')
                            ->label('Notas')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Productos')
                    ->schema([
                        Components\RepeatableEntry::make('items')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                Components\TextEntry::make('product.name')
                                    ->label('Producto'),
                                Components\TextEntry::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric(decimalPlaces: 2),
                                Components\TextEntry::make('unit_price')
                                    ->label('Precio Unit.')
                                    ->money('GTQ'),
                                Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('GTQ')
                                    ->weight('bold'),
                            ]),
                    ]),

                Components\Section::make('Resumen Financiero')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('discount_type')
                            ->label('Tipo de Descuento')
                            ->formatStateUsing(fn (?string $state) => match ($state) {
                                'none', null => 'Sin descuento',
                                'percent' => 'Porcentaje (%)',
                                'fixed' => 'Monto Fijo (Q)',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'none', null => 'gray',
                                default => 'warning',
                            }),

                        Components\TextEntry::make('discount_amount')
                            ->label('Descuento Aplicado')
                            ->money('GTQ')
                            ->visible(fn ($record) => $record->discount_type && $record->discount_type !== 'none'),

                        Components\TextEntry::make('total')
                            ->label('Total')
                            ->money('GTQ')
                            ->size('lg')
                            ->weight('bold')
                            ->color('primary'),
                    ]),

                Components\Section::make('Pagos Registrados')
                    ->schema([
                        Components\RepeatableEntry::make('payments')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                Components\TextEntry::make('payment_method')
                                    ->label('Método de Pago')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                        'cash' => 'Efectivo',
                                        'transfer' => 'Transferencia',
                                        'card' => 'Tarjeta',
                                        default => $state,
                                    })
                                    ->color(fn (string $state) => match ($state) {
                                        'cash' => 'success',
                                        'transfer' => 'info',
                                        'card' => 'warning',
                                        default => 'gray',
                                    }),
                                Components\TextEntry::make('amount')
                                    ->label('Monto')
                                    ->money('GTQ')
                                    ->weight('bold'),
                                Components\TextEntry::make('payment_date')
                                    ->label('Fecha de Pago')
                                    ->dateTime('d/m/Y H:i'),
                                Components\TextEntry::make('notes')
                                    ->label('Notas')
                                    ->placeholder('—'),
                            ])
                            ->placeholder('No hay pagos registrados.'),
                    ]),

                Components\Section::make('Balance')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('total')
                            ->label('Total Venta')
                            ->money('GTQ'),
                        Components\TextEntry::make('total_paid')
                            ->label('Total Pagado')
                            ->money('GTQ')
                            ->color('success'),
                        Components\TextEntry::make('balance')
                            ->label('Saldo Pendiente')
                            ->money('GTQ')
                            ->color(fn ($record) => $record->balance > 0.01 ? 'danger' : 'success'),
                    ]),
            ]);
    }
}
