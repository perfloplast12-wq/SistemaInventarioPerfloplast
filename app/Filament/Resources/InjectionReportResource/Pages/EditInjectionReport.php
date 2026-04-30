<?php

namespace App\Filament\Resources\InjectionReportResource\Pages;

use App\Filament\Resources\InjectionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInjectionReport extends EditRecord
{
    protected static string $resource = InjectionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
