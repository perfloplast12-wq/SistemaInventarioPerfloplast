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

    /** Current display mode: gauge | comparison | trend */
    public string $mode = 'comparison';

    public function setMode(string $m): void
    {
        $this->mode = in_array($m, ['gauge', 'comparison', 'trend']) ? $m : 'comparison';
    }

    /** Returns all chart data in one shot for the view */
    public function getChartData(): array
    {
        $filters     = $this->filters ?? [];
        $start       = Carbon::parse($filters['startDate'] ?? now()->subMonths(2)->startOfMonth())->startOfDay();
        $end         = Carbon::parse($filters['endDate']   ?? now())->endOfDay();
        $productId   = $filters['product_id']   ?? null;

        $shifts = Shift::all();
        $palette = ['#6366f1','#10b981','#f59e0b','#f43f5e','#8b5cf6'];

        // ── Comparison data ──────────────────────────────────────────────
        $compNames = $compValues = [];
        foreach ($shifts as $shift) {
            $q = Production::where('shift_id', $shift->id)->where('status', 'confirmed')
                ->whereBetween('production_date', [$start, $end]);
            if ($productId) $q->where('product_id', $productId);
            $compNames[]  = $shift->name;
            $compValues[] = (float) $q->sum('quantity');
        }

        // ── Trend data ───────────────────────────────────────────────────
        $days        = max(1, $start->diffInDays($end));
        $format      = $days <= 31 ? '%Y-%m-%d' : '%Y-%m';
        $labelFormat = $days <= 31 ? 'd M' : 'M Y';

        $trendLabels = [];
        $curr = $start->copy();
        while ($curr <= $end) {
            $trendLabels[] = $curr->translatedFormat($labelFormat);
            $curr          = $days <= 31 ? $curr->addDay() : $curr->addMonth();
        }

        $trendSeries = [];
        foreach ($shifts as $shift) {
            $raw = Production::where('shift_id', $shift->id)->where('status', 'confirmed')
                ->whereBetween('production_date', [$start, $end])
                ->select(DB::raw("DATE_FORMAT(production_date, '$format') as d"), DB::raw('SUM(quantity) as q'))
                ->groupBy('d')->pluck('q', 'd');

            $values = [];
            $cur2   = $start->copy();
            while ($cur2 <= $end) {
                $k        = $cur2->format($days <= 31 ? 'Y-m-d' : 'Y-m');
                $values[] = (float)($raw[$k] ?? 0);
                $cur2     = $days <= 31 ? $cur2->addDay() : $cur2->addMonth();
            }
            $trendSeries[] = ['name' => $shift->name, 'data' => $values];
        }

        // ── Gauge data ───────────────────────────────────────────────────
        $currentTime   = now()->format('H:i:s');
        $activeShift   = $shifts->filter(function($s) use ($currentTime) {
            if ($s->start_time < $s->end_time) {
                return $currentTime >= $s->start_time && $currentTime <= $s->end_time;
            }
            return $currentTime >= $s->start_time || $currentTime <= $s->end_time;
        })->first() ?? $shifts->first();

        $gaugeData = [];
        if ($activeShift) {
            $real = (float) Production::where('shift_id', $activeShift->id)->where('status', 'confirmed')
                ->whereBetween('production_date', [$start, $end])->sum('quantity');
            $daysCount = max(1, $start->diffInDays($end) + 1);
            $goal      = (float)($activeShift->daily_goal > 0 ? $activeShift->daily_goal * $daysCount : 0);
            $pct       = $goal > 0 ? min(100, round(($real / $goal) * 100)) : 0;
            $color     = $pct >= 100 ? '#22c55e' : ($pct >= 70 ? '#eab308' : ($pct > 0 ? '#ef4444' : '#94a3b8'));
            $gaugeData = compact('pct', 'color', 'real', 'goal') + ['shiftName' => $activeShift->name];
        }

        return compact('compNames', 'compValues', 'trendLabels', 'trendSeries', 'gaugeData', 'palette');
    }
}
