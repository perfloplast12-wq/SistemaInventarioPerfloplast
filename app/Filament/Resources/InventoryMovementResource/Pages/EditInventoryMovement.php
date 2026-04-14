<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use App\Filament\Resources\InventoryMovementResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInventoryMovement extends EditRecord
{
    protected static string $resource = InventoryMovementResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->sanitizeAndValidate($data);
        return $data;
    }

    private function sanitizeAndValidate(array $data): array
    {
        $type = $data['type'] ?? null;

        foreach (['from_warehouse_id','to_warehouse_id','from_truck_id','to_truck_id'] as $k) {
            if (empty($data[$k])) $data[$k] = null;
        }

        if ($type === 'in') {
            $data['from_warehouse_id'] = null;
            $data['from_truck_id'] = null;

            if (!$data['to_warehouse_id'] && !$data['to_truck_id']) {
                $this->fail('Entrada requiere destino (bodega o camión).');
            }
        }

        if ($type === 'out') {
            $data['to_warehouse_id'] = null;
            $data['to_truck_id'] = null;

            if (!$data['from_warehouse_id'] && !$data['from_truck_id']) {
                $this->fail('Salida requiere origen (bodega o camión).');
            }
        }

        if ($type === 'adjust') {
            $data['from_warehouse_id'] = null;
            $data['from_truck_id'] = null;

            if (!$data['to_warehouse_id'] && !$data['to_truck_id']) {
                $this->fail('Ajuste requiere una ubicación (bodega o camión).');
            }
        }

        if ($type === 'transfer') {
            if (!$data['from_warehouse_id'] && !$data['from_truck_id']) {
                $this->fail('Transferencia requiere origen (bodega o camión).');
            }
            if (!$data['to_warehouse_id'] && !$data['to_truck_id']) {
                $this->fail('Transferencia requiere destino (bodega o camión).');
            }

            $sameWarehouse = $data['from_warehouse_id'] && $data['from_warehouse_id'] === $data['to_warehouse_id'];
            $sameTruck     = $data['from_truck_id'] && $data['from_truck_id'] === $data['to_truck_id'];

            if ($sameWarehouse || $sameTruck) {
                $this->fail('Transferencia inválida: origen y destino no pueden ser el mismo.');
            }
        }

        if ($type !== 'in') {
            $data['unit_cost'] = null;
        }

        return $data;
    }

    private function fail(string $message): void
    {
        Notification::make()
            ->title('Validación')
            ->body($message)
            ->danger()
            ->send();

        $this->halt();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    




}
