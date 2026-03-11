<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\InventoryMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryMovementsTrendChart extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.inventory-movements-trend-chart';
    protected static ?int $sort = 6;

    public function getChartData(): array
    {
        $filters = $this->filters ?? [];
        $start   = Carbon::parse($filters['startDate'] ?? now()->startOfMonth())->startOfDay();
        $end     = Carbon::parse($filters['endDate']   ?? now())->endOfDay();

        $days        = max(1, $start->diffInDays($end));
        $format      = $days <= 31 ? '%Y-%m-%d' : '%Y-%m';
        $labelFormat = $days <= 31 ? 'd M' : 'M Y';

        $data = InventoryMovement::query()
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw("DATE_FORMAT(created_at, '$format') as period"), DB::raw('COUNT(*) as count'))
            ->groupBy('period')->pluck('count', 'period');

        $categories = $seriesData = [];
        $curr = $start->copy();
        while ($curr <= $end) {
            $key          = $curr->format($days <= 31 ? 'Y-m-d' : 'Y-m');
            $categories[] = $curr->translatedFormat($labelFormat);
            $seriesData[] = (int)($data[$key] ?? 0);
            $curr         = $days <= 31 ? $curr->addDay() : $curr->addMonth();
        }

        return compact('categories', 'seriesData');
    }
}
