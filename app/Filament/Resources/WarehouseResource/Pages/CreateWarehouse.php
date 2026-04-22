<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
use App\Filament\Pages\Catalogos;
use App\Filament\Resources\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    use HandlesSoftDeletedDuplicates;

    protected static string $resource = WarehouseResource::class;

    protected function getUniqueFieldsForRestore(): array
    {
        return ['code'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_catalogos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(Catalogos::getUrl()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
