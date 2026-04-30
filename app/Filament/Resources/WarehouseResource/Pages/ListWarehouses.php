<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Pages\Inventario;
use App\Filament\Resources\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_inventario')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(Inventario::getUrl()),

            Actions\CreateAction::make()
                ->label('Crear Bodega'),
        ];
    }
}
