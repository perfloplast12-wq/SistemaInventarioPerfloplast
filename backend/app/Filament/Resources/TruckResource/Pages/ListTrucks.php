<?php

namespace App\Filament\Resources\TruckResource\Pages;

use App\Filament\Pages\Catalogos;
use App\Filament\Resources\TruckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrucks extends ListRecords
{
    protected static string $resource = TruckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_catalogos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(Catalogos::getUrl()),

            Actions\CreateAction::make()
                ->label('Crear Camión'),
        ];
    }
}
