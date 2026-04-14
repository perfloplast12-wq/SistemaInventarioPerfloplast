<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    public function getTitle(): string
    {
        return 'Editar Venta: ' . ($this->record->sale_number ?? 'Borrador');
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Forzar origen a camión virtualmente para que la vista lo reconozca si existiera un campo
        $data['origin_type'] = 'truck';
        
        // Convertir null a 'null' para que coincida con la opción del selector al cargar
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                if (!isset($item['color_id']) || $item['color_id'] === null) {
                    $data['items'][$key]['color_id'] = 'null';
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Normalizar color_id de 'null' de vuelta a null
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                if (isset($item['color_id']) && $item['color_id'] === 'null') {
                    $data['items'][$key]['color_id'] = null;
                }
            }
        }
        return $data;
    }

    protected function beforeSave(): void
    {
        // Solo permitir edición si está en estado draft
        if ($this->record->status !== 'draft') {
            Notification::make()
                ->title('Error')
                ->body('Solo se pueden editar ventas en estado Borrador.')
                ->danger()
                ->send();
            throw new \RuntimeException('No se puede editar venta confirmada.');
        }

        // Validar que al menos un item tenga quantity > 0
        if (empty($this->data['items']) || count($this->data['items']) === 0) {
            Notification::make()
                ->title('Error')
                ->body('Debe agregar al menos un producto.')
                ->danger()
                ->send();
            throw new \RuntimeException('Sin productos');
        }

        // Calcular totales en backend
        $totalItems = 0;
        foreach ($this->data['items'] as $item) {
            if (!empty($item['subtotal'])) {
                $totalItems += (float)$item['subtotal'];
            }
        }

        $subtotal = $totalItems;

        // Calcular descuento si aplica
        $discountType = $this->data['discount_type'] ?? 'none';
        $discountValue = (float)($this->data['discount_value'] ?? 0);
        $discountAmount = 0.0;
        if ($discountType === 'percent') {
            $discountAmount = $subtotal * ($discountValue / 100.0);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        }

        if ($discountAmount < 0) {
            Notification::make()->title('Error')->body('El descuento no puede ser negativo.')->danger()->send();
            throw new \RuntimeException('Descuento negativo');
        }
        if ($discountAmount > $subtotal) {
            Notification::make()->title('Error')->body('El descuento no puede ser mayor al subtotal.')->danger()->send();
            throw new \RuntimeException('Descuento mayor al subtotal');
        }

        $this->data['discount_amount'] = $discountAmount;
        $this->data['total'] = $subtotal - $discountAmount;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('✓ Venta actualizada')
            ->body('Los cambios han sido guardados correctamente.')
            ->success()
            ->send();
    }
}
