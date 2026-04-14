<?php

namespace App\Filament\Resources\UnitOfMeasureResource\Pages;

use App\Filament\Pages\Catalogos;
use App\Filament\Resources\UnitOfMeasureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnitOfMeasure extends EditRecord
{
    protected static string $resource = UnitOfMeasureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_catalogos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(Catalogos::getUrl()),

            Actions\DeleteAction::make()->label('Eliminar'),
        ];
    }
}
