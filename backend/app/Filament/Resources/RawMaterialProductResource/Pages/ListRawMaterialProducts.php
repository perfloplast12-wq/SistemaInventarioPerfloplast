<?php

namespace App\Filament\Resources\RawMaterialProductResource\Pages;

use App\Filament\Resources\RawMaterialProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRawMaterialProducts extends ListRecords
{
    protected static string $resource = RawMaterialProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver a Inventario')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.admin.pages.inventario'))
                ->color('gray'),

            Actions\CreateAction::make()->label('Crear Materia Prima'),
        ];
    }
}
