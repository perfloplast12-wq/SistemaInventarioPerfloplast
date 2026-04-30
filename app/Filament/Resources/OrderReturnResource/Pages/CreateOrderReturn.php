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
