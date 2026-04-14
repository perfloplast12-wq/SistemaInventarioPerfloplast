<?php

namespace App\Filament\Resources\RawMaterialProductResource\Pages;

use App\Filament\Resources\RawMaterialProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRawMaterialProduct extends CreateRecord
{
    protected static string $resource = RawMaterialProductResource::class;


    protected function getHeaderActions(): array
{
    return [
        \Filament\Actions\Action::make('volver')
            ->label('Volver')
            ->icon('heroicon-o-arrow-left')
            ->url($this->getResource()::getUrl('index'))
            ->color('gray'),
    ];
}

}
