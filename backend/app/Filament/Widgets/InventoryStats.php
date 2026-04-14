<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStats extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Product::query()->count();
        $raw   = Product::query()->where('type', 'raw_material')->count();
        $fin   = Product::query()->where('type', 'finished_product')->count();

        return [
            Stat::make('Total productos', $total)
                ->description('Materia prima + Producto terminado')
                ->color('primary'),

            Stat::make('Materia prima', $raw)
                ->description('Registrados como materia prima')
                ->color('warning'),
            Stat::make('Producto terminado', $fin)
                ->description('Registrados como producto terminado')
                ->color('success'),
        ];
    }
}
