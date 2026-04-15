<?php

namespace App\Filament\Resources\UnitOfMeasureResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
use App\Filament\Pages\Catalogos;
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
            Actions\Action::make('volver_catalogos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(Catalogos::getUrl()),
        ];
    }
}
