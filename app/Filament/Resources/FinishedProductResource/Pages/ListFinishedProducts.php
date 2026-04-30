<?php

namespace App\Filament\Resources\FinishedProductResource\Pages;

use App\Filament\Resources\FinishedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinishedProducts extends ListRecords
{
    protected static string $resource = FinishedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver a Inventario')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.admin.pages.inventario'))
                ->color('gray'),

            Actions\Action::make('gestionar_colores')
                ->label('Gestionar Colores')
                ->icon('heroicon-o-swatch')
                ->url(\App\Filament\Resources\ColorResource::getUrl('index'))
                ->color('info'),

            Actions\CreateAction::make()->label('Crear Producto terminado'),
        ];
    }
}
