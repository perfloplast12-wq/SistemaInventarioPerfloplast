<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopSellingProductsChart extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.top-selling-products-chart';
    protected static ?int $sort = 7;

    public ?int $local_warehouse_id = null;

    public function getChartData(): array
    {
        $filters = $this->filters ?? [];
        $start   = Carbon::parse($filters['startDate'] ?? now()->startOfMonth())->startOfDay();
        $end     = Carbon::parse($filters['endDate']   ?? now())->endOfDay();

        $results = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'confirmed')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->when($this->local_warehouse_id, fn($q) => $q->where('sales.from_warehouse_id', $this->local_warehouse_id))
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.quantity * (sale_items.unit_price - COALESCE(products.cost_price, 0))) as total_profit')
            )
            ->groupBy('products.name', 'products.id')
            ->orderByDesc('total_profit')
            ->limit(5)->get();

        $labels  = $results->pluck('name')->toArray();
        $profits = $results->pluck('total_profit')->map(fn($v) => round((float)$v))->toArray();

        $warehouses = \App\Models\Warehouse::where('is_active', true)->pluck('name', 'id');

        return compact('labels', 'profits', 'warehouses');
    }
}
