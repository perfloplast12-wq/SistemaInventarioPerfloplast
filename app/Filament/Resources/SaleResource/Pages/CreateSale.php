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
        // Calculate subtotal from items for the Sale record
        $subtotal = 0;
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $subtotal += (float)($item['subtotal'] ?? ((float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0)));
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
        
        // Ensure required sale_date is set since it has no default in DB
        if (empty($data['sale_date'])) {
            $data['sale_date'] = now();
        }
        
        // Remove non-model fields that are not columns in the sales table
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
                'payment_method' => $formData['payment_method'] ?? 'cash',
                'amount' => $paymentAmount,
                'payment_date' => now(),
            ]);
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Venta registrada')
            ->body('La venta se ha creado correctamente en estado borrador.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
