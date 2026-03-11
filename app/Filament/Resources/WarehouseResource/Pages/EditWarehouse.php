<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Pages\Catalogos;
use App\Filament\Resources\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_catalogos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => $this->getResource()::getUrl('index')),

            Actions\DeleteAction::make()
                ->label('Eliminar'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
