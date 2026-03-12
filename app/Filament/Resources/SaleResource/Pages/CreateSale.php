<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\SaleService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    public function getTitle(): string
    {
        return 'Crear Nueva Venta';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Volver')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Finalizar Venta');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Finalizar y Crear otro');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculate totals in backend and normalize color_id
        $subtotal = 0;
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                $subtotal += (float)($item['subtotal'] ?? 0);
                
                // Convert string 'null' from select back to actual null for database
                if (isset($item['color_id']) && $item['color_id'] === 'null') {
                    $data['items'][$key]['color_id'] = null;
                }
            }
        }

        $discountType = $data['discount_type'] ?? 'none';
        $discountValue = (float)($data['discount_value'] ?? 0);
        $discountAmount = 0.0;
        if ($discountType === 'percent') {
            $discountAmount = $subtotal * ($discountValue / 100.0);
        } elseif ($discountType === 'fixed') {
            $discountAmount = min($subtotal, $discountValue);
        }

        $data['discount_amount'] = $discountAmount;
        $data['total'] = max(0, $subtotal - $discountAmount);
        
        $data['origin_type'] = 'truck';
        $data['from_warehouse_id'] = null;

        // Remove non-model fields
        unset($data['payment_method'], $data['payment_amount']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $formData = $this->form->getRawState();

        // Create payment if amount > 0
        $paymentAmount = (float)($formData['payment_amount'] ?? 0);
        if ($paymentAmount > 0) {
            $this->record->payments()->create([
                'method' => $formData['payment_method'] ?? 'cash',
                'amount' => $paymentAmount,
                'payment_date' => now(),
            ]);
        }

        Notification::make()
            ->title('✓ Venta creada como borrador')
            ->body('Revisá los datos y confirmá la venta.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
