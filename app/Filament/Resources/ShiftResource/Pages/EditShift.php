<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Pages\Catalogos;
use App\Filament\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShift extends EditRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_catalogos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => $this->getResource()::getUrl('index')),

            Actions\DeleteAction::make()
                ->label('Eliminar'),
        ];
    }


       protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
