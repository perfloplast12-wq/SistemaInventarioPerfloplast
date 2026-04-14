<?php

namespace App\Filament\Resources\OrderReturnResource\Pages;

use App\Filament\Resources\OrderReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderReturn extends CreateRecord
{
    protected static string $resource = OrderReturnResource::class;

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
