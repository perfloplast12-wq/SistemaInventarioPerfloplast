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
            Actions\Action::make('pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn ($record) => url('/admin/injection-reports/'.$record->id.'/pdf'))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
