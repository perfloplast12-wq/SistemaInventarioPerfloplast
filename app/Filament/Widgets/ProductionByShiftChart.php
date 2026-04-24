<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Production;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionByShiftChart extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.production-by-shift-chart';
    protected static ?int $sort = 9;
    protected static ?string $pollingInterval = '30s';

    /** Current display mode: gauge | comparison | trend */
    public string $mode = 'comparison';

    public function setMode(string $m): void
    {
        $this->mode = in_array($m, ['comparison', 'trend']) ? $m : 'comparison';
    }

    public function getChartData(): array
    {
        $filters     = $this->filters ?? [];
        $start       = Carbon::parse($filters['startDate'] ?? now()->subDays(30))->startOfDay();
        $end         = Carbon::parse($filters['endDate']   ?? now())->endOfDay();
        $productId   = $filters['product_id']   ?? null;

        $cacheKey = 'prod_shift_chart_' . md5(serialize($filters) . $this->mode);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($start, $end, $productId) {
            $shifts = Shift::all();
            $palette = ['#6366f1','#10b981','#f59e0b','#f43f5e','#8b5cf6'];

            // ── Comparison data (Agrupado en una sola consulta) ─────────────
            $compData = Production::where('status', 'confirmed')
                ->whereBetween('production_date', [$start, $end])
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->select('shift_id', DB::raw('SUM(quantity) as total'))
                ->groupBy('shift_id')
                ->pluck('total', 'shift_id');

            $compNames = $compValues = [];
            foreach ($shifts as $shift) {
                $compNames[]  = $shift->name;
                $compValues[] = (float)($compData[$shift->id] ?? 0);
            }

            // ── Trend data ───────────────────────────────────────────────────
            $days        = max(1, $start->diffInDays($end));
            $format      = $days <= 62 ? '%Y-%m-%d' : '%Y-%m';
            $labelFormat = $days <= 62 ? 'd M' : 'M Y';

            $trendLabels = [];
            $curr = $start->copy();
            while ($curr <= $end) {
                $trendLabels[] = $curr->translatedFormat($labelFormat);
                $curr          = $days <= 31 ? $curr->addDay() : $curr->addMonth();
            }

            // Traer todos los datos del trend en una sola consulta
            $trendRaw = Production::where('status', 'confirmed')
                ->whereBetween('production_date', [$start, $end])
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->select('shift_id', DB::raw("DATE_FORMAT(production_date, '$format') as d"), DB::raw('SUM(quantity) as q'))
                ->groupBy('shift_id', 'd')
                ->get()
                ->groupBy('shift_id');

            $trendSeries = [];
            foreach ($shifts as $shift) {
                $shiftTrend = $trendRaw->get($shift->id)?->pluck('q', 'd') ?? collect();
                $values = [];
                $cur2   = $start->copy();
                while ($cur2 <= $end) {
                    $k        = $cur2->format($days <= 31 ? 'Y-m-d' : 'Y-m');
                    $values[] = (float)($shiftTrend[$k] ?? 0);
                    $cur2     = $days <= 31 ? $cur2->addDay() : $cur2->addMonth();
                }
                $trendSeries[] = ['name' => $shift->name, 'data' => $values];
            }

            return compact('compNames', 'compValues', 'trendLabels', 'trendSeries', 'palette');
        });
    }
}
