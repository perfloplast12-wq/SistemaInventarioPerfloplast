<?php

namespace App\Filament\Resources\DispatchResource\Pages;

use App\Filament\Resources\DispatchResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDispatch extends EditRecord
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total_value'] = 0;
        $data['total_products'] = 0;
        $data['product_types'] = 0;
        
        // Asegurar que driver_name no sea nulo (columna NOT NULL en DB)
        if (empty($data['driver_name']) && !empty($data['driver_id'])) {
            $data['driver_name'] = \App\Models\User::find($data['driver_id'])?->name ?? 'Piloto';
        }
        
        if (empty($data['driver_name'])) {
            $data['driver_name'] = 'Sin asignar';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $orderIds = $this->data['selected_orders'] ?? [];
        
        // Desvincular pedidos que ya no están en la lista
        Order::where('dispatch_id', $record->id)
            ->whereNotIn('id', $orderIds)
            ->update(['dispatch_id' => null, 'status' => 'pending']);

        // Vincular nuevos pedidos
        if (!empty($orderIds)) {
            Order::whereIn('id', $orderIds)->update(['dispatch_id' => $record->id]);
        }
        
        // Recalcular totales AHORA que los items fueron guardados por Filament
        if (method_exists($record, 'recalculateTotals')) {
            $record->recalculateTotals();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
