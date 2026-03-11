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
        // Calcular totales con redondeo
        $totalValue = 0;
        $totalProducts = 0;
        $items = $data['items'] ?? [];
        
        foreach ($items as $item) {
            $totalValue += round((float)($item['subtotal'] ?? 0), 2);
            $totalProducts += round((float)($item['quantity'] ?? 0), 3);
        }

        $data['total_value'] = round($totalValue, 2);
        $data['total_products'] = (float) $totalProducts;
        $data['product_types'] = count($items);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $orderIds = $this->data['orders'] ?? [];
        
        // Desvincular pedidos que ya no están en la lista
        Order::where('dispatch_id', $record->id)
            ->whereNotIn('id', $orderIds)
            ->update(['dispatch_id' => null, 'status' => 'pending']);

        // Vincular nuevos pedidos
        if (!empty($orderIds)) {
            Order::whereIn('id', $orderIds)->update(['dispatch_id' => $record->id]);
        }
    }
}
