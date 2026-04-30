<?php

namespace App\Filament\Resources\TruckResource\Pages;

use App\Filament\Resources\DispatchResource;
use App\Filament\Resources\TruckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrucks extends ListRecords
{
    protected static string $resource = TruckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_despachos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(DispatchResource::getUrl('index')),

            Actions\CreateAction::make()
                ->label('Crear Camión'),
        ];
    }
}
