<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver_usuarios')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(UserResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
