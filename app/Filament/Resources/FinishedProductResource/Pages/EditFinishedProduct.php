<?php

namespace App\Filament\Resources\FinishedProductResource\Pages;

use App\Filament\Resources\FinishedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinishedProduct extends EditRecord
{
    protected static string $resource = FinishedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.pages.inventario');
    }
}
