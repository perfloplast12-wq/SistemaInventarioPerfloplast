<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Sale;
use App\Models\Production;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesByPeriodChart extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.sales-by-period-chart';
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    public ?int $warehouse_id = null;

    public function getChartData(): array
    {
        $filters = $this->filters ?? [];
        $start = Carbon::parse($filters['startDate'] ?? now()->startOfMonth())->startOfDay();
        $end   = Carbon::parse($filters['endDate']   ?? now())->endOfDay();

        $days        = max(1, $start->diffInDays($end));
        $format      = $days <= 31 ? '%Y-%m-%d' : '%Y-%m';
        $labelFormat = $days <= 31 ? 'd M' : 'M Y';

        $sales = Sale::where('status', 'confirmed')
            ->whereBetween('sale_date', [$start, $end])
            ->when($this->warehouse_id, fn($q) => $q->where('from_warehouse_id', $this->warehouse_id))
            ->select(DB::raw("DATE_FORMAT(sale_date, '$format') as period"), DB::raw('SUM(total) as val'))
            ->groupBy('period')->pluck('val', 'period');

        $prod = Production::with('toWarehouse')->where('status', 'confirmed')
            ->whereBetween('production_date', [$start, $end])
            ->when($this->warehouse_id, fn($q) => $q->where('to_warehouse_id', $this->warehouse_id))
            ->select(DB::raw("DATE_FORMAT(production_date, '$format') as period"), DB::raw('SUM(quantity) as val'))
            ->groupBy('period')->pluck('val', 'period');

        $labels = $salesData = $prodData = [];
        $curr = $start->copy();
        while ($curr <= $end) {
            $key        = $curr->format($days <= 31 ? 'Y-m-d' : 'Y-m');
            $labels[]   = $curr->translatedFormat($labelFormat);
            $salesData[]= round((float)($sales[$key] ?? 0));
            $prodData[] = round((float)($prod[$key] ?? 0));
            $curr       = $days <= 31 ? $curr->addDay() : $curr->addMonth();
        }

        $warehouses = \App\Models\Warehouse::where('is_active', true)->pluck('name', 'id');

        return compact('labels', 'salesData', 'prodData', 'warehouses');
    }
}
