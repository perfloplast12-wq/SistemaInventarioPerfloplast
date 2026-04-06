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
        $data = Product::where('type', 'raw_material')
            ->where('is_active', true)
            ->withSum('stocks', 'quantity')
            ->get()
            ->filter(fn($p) => (float)$p->stocks_sum_quantity > 0)
            ->sortByDesc('stocks_sum_quantity');

        $labels = $data->pluck('name')->toArray();
        $series = $data->pluck('stocks_sum_quantity')->map(fn($v) => (float)$v)->toArray();

        return compact('labels', 'series');
    }
}
