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
            Actions\CreateAction::make(),
        ];
    }
}
