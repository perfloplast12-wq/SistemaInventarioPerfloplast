<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitabilityTrendChart extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.profitability-trend-chart';
    protected static ?int $sort = 3;

    public ?int $local_warehouse_id = null;

    public function getChartData(): array
    {
        $filters = $this->filters ?? [];
        $start   = Carbon::parse($filters['startDate'] ?? now()->subMonths(2)->startOfMonth())->startOfDay();
        $end     = Carbon::parse($filters['endDate']   ?? now())->endOfDay();

        $days        = max(1, $start->diffInDays($end));
        $format      = $days <= 31 ? '%Y-%m-%d' : '%Y-%m';
        $labelFormat = $days <= 31 ? 'd M' : 'M Y';

        $incomeRaw = Sale::where('status', 'confirmed')->whereBetween('sale_date', [$start, $end])
            ->when($this->local_warehouse_id, fn($q) => $q->where('from_warehouse_id', $this->local_warehouse_id))
            ->select(DB::raw("DATE_FORMAT(sale_date, '$format') as period"), DB::raw('SUM(total) as val'))
            ->groupBy('period')->pluck('val', 'period');

        $costsRaw = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'confirmed')->whereBetween('sales.sale_date', [$start, $end])
            ->when($this->local_warehouse_id, fn($q) => $q->where('sales.from_warehouse_id', $this->local_warehouse_id))
            ->select(DB::raw("DATE_FORMAT(sales.sale_date, '$format') as period"),
                     DB::raw('SUM(sale_items.quantity * COALESCE(products.cost_price, 0)) as val'))
            ->groupBy('period')->pluck('val', 'period');

        $labels = $income = $profit = [];
        $curr   = $start->copy();
        while ($curr <= $end) {
            $key      = $curr->format($days <= 31 ? 'Y-m-d' : 'Y-m');
            $labels[] = $curr->translatedFormat($labelFormat);
            $inc      = round((float)($incomeRaw[$key] ?? 0));
            $cost     = round((float)($costsRaw[$key] ?? 0));
            $income[] = $inc;
            $profit[] = $inc - $cost;
            $curr     = $days <= 31 ? $curr->addDay() : $curr->addMonth();
        }

        $warehouses = \App\Models\Warehouse::where('is_active', true)->pluck('name', 'id');

        return compact('labels', 'income', 'profit', 'warehouses');
    }
}
