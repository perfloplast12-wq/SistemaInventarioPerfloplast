<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use App\Filament\Resources\InventoryMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryMovement extends CreateRecord
{
    protected static string $resource = InventoryMovementResource::class;

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
