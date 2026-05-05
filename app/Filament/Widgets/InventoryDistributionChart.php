<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Product;

class InventoryDistributionChart extends Widget
{
    protected static string $view = 'filament.widgets.inventory-distribution-chart';
    protected static ?int $sort = 5;
    protected static bool $isLazy = true;

    public function getChartData(): array
    {
        $data = Product::query()
            ->where('type', 'raw_material')
            ->isActive()
            ->withSum('stocks as stocks_sum_quantity', 'quantity')
            ->get()
            ->filter(fn($p) => (float)($p->stocks_sum_quantity ?? 0) > 0)
            ->sortByDesc('stocks_sum_quantity');

        $labels = $data->pluck('name')->toArray();
        $series = $data->pluck('stocks_sum_quantity')->map(fn($v) => (float)($v ?? 0))->toArray();

        return compact('labels', 'series');
    }
}
