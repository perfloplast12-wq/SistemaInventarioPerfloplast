<?php

namespace App\Filament\Resources\RawMaterialProductResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
use App\Filament\Resources\RawMaterialProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRawMaterialProduct extends CreateRecord
{
    use HandlesSoftDeletedDuplicates;

    protected static string $resource = RawMaterialProductResource::class;

    protected function getUniqueFieldsForRestore(): array
    {
        return ['sku'];
    }


    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('volver')
                ->label('Volver a Inventario')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.admin.pages.inventario'))
                ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.pages.inventario');
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();
        
        if (!empty($data['initial_stock']) && !empty($data['initial_warehouse_id'])) {
            \App\Models\InventoryMovement::create([
                'type' => 'in',
                'product_id' => $this->record->id,
                'to_warehouse_id' => $data['initial_warehouse_id'],
                'quantity' => $data['initial_stock'],
                'unit_cost' => 0,
                'note' => 'Stock inicial al registrar producto',
                'created_by' => auth()->id(),
            ]);
        }
    }
}
