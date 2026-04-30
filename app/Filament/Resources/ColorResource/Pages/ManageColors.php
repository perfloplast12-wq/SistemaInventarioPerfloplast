<?php

namespace App\Filament\Resources\ColorResource\Pages;

use App\Filament\Resources\ColorResource;
use App\Filament\Resources\FinishedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageColors extends ManageRecords
{
    protected static string $resource = ColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_productos')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(FinishedProductResource::getUrl('index'))
                ->color('gray'),

            Actions\CreateAction::make(),
        ];
    }
}
