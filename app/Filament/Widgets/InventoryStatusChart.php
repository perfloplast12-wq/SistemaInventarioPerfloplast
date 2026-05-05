<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Product;

class InventoryStatusChart extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.inventory-status-chart';
    protected static ?int $sort = 11;
    protected static bool $isLazy = true;

    public ?int $local_warehouse_id = null;

    public function getChartData(): array
    {
        $filters     = $this->filters ?? [];
        $warehouseId = $this->local_warehouse_id ?? $filters['warehouse_id'] ?? null;

        $products = Product::query()
            ->where('is_active', true)
            ->withSum(['stocks as total_stock' => function ($q) use ($warehouseId) {
                $q->whereNotNull('warehouse_id');
                if ($warehouseId) $q->where('warehouse_id', $warehouseId);
            }], 'quantity')
            ->get();

        $normal = $low = $critical = 0;
        foreach ($products as $p) {
            $qty = $p->convertToPresentationUnit((float)($p->total_stock ?? 0));
            if ($qty <= 3)      $critical++;
            elseif ($qty <= 10) $low++;
            else                $normal++;
        }

        $warehouses = \App\Models\Warehouse::where('is_active', true)->pluck('name', 'id');

        return compact('normal', 'low', 'critical', 'warehouses');
    }
}
