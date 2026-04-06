<?php

namespace App\Filament\Resources\FinishedProductResource\Pages;

use App\Filament\Resources\FinishedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFinishedProduct extends CreateRecord
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
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.pages.inventario');
    }
}
