<?php

namespace App\Filament\Resources\DispatchResource\Pages;

use App\Filament\Resources\DispatchResource;
use App\Models\Order;
use Filament\Resources\Pages\CreateRecord;

class CreateDispatch extends CreateRecord
{
    protected static string $resource = DispatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total_value'] = 0;
        $data['total_products'] = 0;
        $data['product_types'] = 0;
        $data['created_by'] = auth()->id();
        
        // Asegurar que driver_name no sea nulo (columna NOT NULL en DB)
        if (empty($data['driver_name']) && !empty($data['driver_id'])) {
            $data['driver_name'] = \App\Models\User::find($data['driver_id'])?->name ?? 'Piloto';
        }
        
        if (empty($data['driver_name'])) {
            $data['driver_name'] = 'Sin asignar';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $orderIds = $this->data['selected_orders'] ?? [];
        
        if (!empty($orderIds)) {
            Order::whereIn('id', $orderIds)->update(['dispatch_id' => $record->id]);
        }
        
        // Recalcular los totales AHORA que los items ya fueron guardados en la BD por Filament
        if (method_exists($record, 'recalculateTotals')) {
            $record->recalculateTotals();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
