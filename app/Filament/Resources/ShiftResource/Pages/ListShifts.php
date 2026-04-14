<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Pages\Catalogos;
use App\Filament\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_catalogos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(Catalogos::getUrl()),

            Actions\CreateAction::make()->label('Crear Turno'),
        ];
    }
}
