<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('apariencia')
                ->label('Apariencia')
                ->icon('heroicon-o-paint-brush')
                ->color('info')
                ->url(\App\Filament\Pages\AppearanceSettings::getUrl())
                ->visible(fn () => auth()->user()?->hasRole('super_admin')),

            Actions\Action::make('bitacora')
                ->label('Bitácora')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning')
                ->url(\App\Filament\Resources\AuditLogResource::getUrl('index'))
                ->visible(fn () => auth()->user()?->can('audit.view')),

            Actions\CreateAction::make()->label('Crear usuario'),
        ];
    }
}
