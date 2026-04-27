<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Product;

class InventoryDistributionChart extends Widget
{
    protected static string $view = 'filament.widgets.inventory-distribution-chart';
    protected static ?int $sort = 5;

    public function getChartData(): array
    {
        $data = Product::query()
            ->where('type', 'raw_material')
            ->isActive()
            ->withSum('stocks', 'quantity')
            ->having('stocks_sum_quantity', '>', 0)
            ->orderByDesc('stocks_sum_quantity')
            ->get();

        $labels = $data->pluck('name')->toArray();
        $series = $data->pluck('stocks_sum_quantity')->map(fn($v) => (float)$v)->toArray();

        return compact('labels', 'series');
    }
}
