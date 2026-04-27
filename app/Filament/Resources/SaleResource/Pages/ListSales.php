<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToViews;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_map')
                ->label('Mapa de Vendedores')
                ->icon('heroicon-o-map')
                ->color('info')
                ->url(fn () => static::$resource::getUrl('map')),

            Actions\CreateAction::make()
                ->label('+ Nueva Venta')
                ->icon('heroicon-o-document-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\SaleResource\Widgets\SalesOverview::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Gestión de Ventas';
    }
}
