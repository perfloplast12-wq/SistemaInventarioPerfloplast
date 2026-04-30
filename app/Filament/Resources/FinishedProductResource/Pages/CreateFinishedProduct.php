<?php

namespace App\Filament\Resources\FinishedProductResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
use App\Filament\Resources\FinishedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFinishedProduct extends CreateRecord
{
    use HandlesSoftDeletedDuplicates;

    protected static string $resource = FinishedProductResource::class;

    protected function getUniqueFieldsForRestore(): array
    {
        return ['name'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
