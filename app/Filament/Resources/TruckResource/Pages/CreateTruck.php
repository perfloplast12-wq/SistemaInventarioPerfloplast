<?php

namespace App\Filament\Resources\TruckResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
use App\Filament\Pages\Catalogos;
use App\Filament\Resources\TruckResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTruck extends CreateRecord
{
    use HandlesSoftDeletedDuplicates;

    protected static string $resource = TruckResource::class;

    protected function getUniqueFieldsForRestore(): array
    {
        return ['plate'];
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
