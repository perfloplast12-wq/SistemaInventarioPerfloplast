<?php

namespace App\Filament\Resources\RawMaterialProductResource\Pages;

use App\Filament\Resources\RawMaterialProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRawMaterialProduct extends CreateRecord
{
    protected static string $resource = RawMaterialProductResource::class;


    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('volver')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
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
