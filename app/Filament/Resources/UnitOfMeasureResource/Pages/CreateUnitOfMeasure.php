<?php

namespace App\Filament\Resources\UnitOfMeasureResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
use App\Filament\Resources\UnitOfMeasureResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitOfMeasure extends CreateRecord
{
    use HandlesSoftDeletedDuplicates;

    protected static string $resource = UnitOfMeasureResource::class;

    protected function getUniqueFieldsForRestore(): array
    {
        return ['name'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(UnitOfMeasureResource::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
