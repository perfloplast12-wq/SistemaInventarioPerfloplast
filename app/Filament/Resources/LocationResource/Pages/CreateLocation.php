<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    use HandlesSoftDeletedDuplicates;

    protected static string $resource = LocationResource::class;

    protected function getUniqueFieldsForRestore(): array
    {
        return ['code'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
