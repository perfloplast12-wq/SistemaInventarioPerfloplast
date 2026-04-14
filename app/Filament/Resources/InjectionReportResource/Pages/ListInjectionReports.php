<?php

namespace App\Filament\Resources\InjectionReportResource\Pages;

use App\Filament\Resources\InjectionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInjectionReports extends ListRecords
{
    protected static string $resource = InjectionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
