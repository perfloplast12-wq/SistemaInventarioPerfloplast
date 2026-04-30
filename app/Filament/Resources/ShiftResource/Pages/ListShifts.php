<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ProductionResource;
use App\Filament\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_produccion')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(ProductionResource::getUrl('index')),

            Actions\CreateAction::make()->label('Crear Turno'),
        ];
    }
}
