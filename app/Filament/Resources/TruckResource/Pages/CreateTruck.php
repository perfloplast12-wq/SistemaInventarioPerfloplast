<?php

namespace App\Filament\Resources\TruckResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
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
            Actions\Action::make('volver')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(TruckResource::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
