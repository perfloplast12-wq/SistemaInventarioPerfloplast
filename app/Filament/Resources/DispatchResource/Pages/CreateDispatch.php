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
        // Calcular totales con redondeo para precisión decimal
        $totalValue = 0;
        $totalProducts = 0;
        $items = $data['items'] ?? [];
        
        foreach ($items as $item) {
            $totalValue += round((float)($item['subtotal'] ?? 0), 2);
            $totalProducts += round((float)($item['quantity'] ?? 0), 3);
        }

        $data['total_value'] = round($totalValue, 2);
        $data['total_products'] = $totalProducts;
        $data['product_types'] = count($items);
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $orderIds = $this->data['selected_orders'] ?? [];
        
        if (!empty($orderIds)) {
            Order::whereIn('id', $orderIds)->update(['dispatch_id' => $record->id]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
