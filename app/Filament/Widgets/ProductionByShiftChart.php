<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Production;
use App\Models\Shift;
use App\Models\ProductionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionByShiftChart extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.production-by-shift-chart';
    protected static bool $isLazy = true;
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
        $filters = [];
        
        // Read filters from the table livewire state if rendered inside a ListRecords page
        if (isset($this->livewire) && isset($this->livewire->tableFilters)) {
            $tableFilters = $this->livewire->tableFilters;
            
            $prodDate = $tableFilters['production_date'] ?? [];
            $filters['startDate'] = $prodDate['from'] ?? null;
            $filters['endDate']   = $prodDate['until'] ?? null;
            
            $shiftFilter = $tableFilters['shift_id'] ?? [];
            $filters['shift_id']  = $shiftFilter['value'] ?? null;
            
            $statusFilter = $tableFilters['status'] ?? [];
            $filters['status']    = $statusFilter['value'] ?? null;
        } else {
            $filters = $this->filters ?? [];
        }

        $start       = Carbon::parse($filters['startDate'] ?? now()->subDays(30))->startOfDay();
        $end         = Carbon::parse($filters['endDate']   ?? now())->endOfDay();
        $productId   = $filters['product_id']   ?? null;
        $activeStatus = $filters['status']      ?? 'confirmed';

        // Auto-zoom/crop the timeline to focus only on active production dates with a 2-day visual margin
        try {
            $activeProductionDates = Production::where('status', $activeStatus)
                ->whereBetween('production_date', [$start, $end])
                ->when(!empty($filters['shift_id']), fn($q) => $q->where('shift_id', $filters['shift_id']))
                ->pluck('production_date')
                ->map(fn($d) => Carbon::parse($d))
                ->sortBy(fn($d) => $d->timestamp);

            if ($activeProductionDates->isNotEmpty()) {
                $firstActive = $activeProductionDates->first()->copy()->subDays(2)->startOfDay();
                $lastActive = $activeProductionDates->last()->copy()->addDays(2)->endOfDay();

                if ($firstActive->gt($start)) {
                    $start = $firstActive;
                }
                if ($lastActive->lt($end)) {
                    $end = $lastActive;
                }
            }
        } catch (\Exception $e) {
            // Fallback to standard range on error
        }

        $cacheKey = 'prod_shift_chart_' . md5(serialize($filters) . $start->toDateString() . $end->toDateString() . $this->mode);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($start, $end, $productId, $filters, $activeStatus) {
            $shifts = Shift::all();
            $palette = ['#6366f1','#10b981','#f59e0b','#f43f5e','#8b5cf6'];

            // ── Comparison data (Joins production_items for output quantity) ─────────────
            $compData = ProductionItem::where('type', 'output')
                ->whereHas('production', function($q) use ($start, $end, $filters, $activeStatus) {
                    $q->where('status', $activeStatus)
                      ->whereBetween('production_date', [$start, $end])
                      ->when(!empty($filters['shift_id']), fn($sq) => $sq->where('shift_id', $filters['shift_id']));
                })
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->join('productions', 'production_items.production_id', '=', 'productions.id')
                ->select('productions.shift_id', DB::raw('SUM(production_items.quantity) as total'))
                ->groupBy('productions.shift_id')
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
            $trendRaw = ProductionItem::where('type', 'output')
                ->whereHas('production', function($q) use ($start, $end, $filters, $activeStatus) {
                    $q->where('status', $activeStatus)
                      ->whereBetween('production_date', [$start, $end])
                      ->when(!empty($filters['shift_id']), fn($sq) => $sq->where('shift_id', $filters['shift_id']));
                })
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->join('productions', 'production_items.production_id', '=', 'productions.id')
                ->select('productions.shift_id', DB::raw("DATE_FORMAT(productions.production_date, '$format') as d"), DB::raw('SUM(production_items.quantity) as q'))
                ->groupBy('productions.shift_id', 'd')
                ->get()
                ->groupBy('shift_id');

            $trendSeries = [];
            foreach ($shifts as $shift) {
                $shiftTrend = $trendRaw->get($shift->id)?->pluck('q', 'd') ?? collect();
                $values = [];
                $cur2   = $start->copy();
                while ($cur2 <= $end) {
                    $k        = $cur2->format($days <= 62 ? 'Y-m-d' : 'Y-m');
                    $values[] = (float)($shiftTrend[$k] ?? 0);
                    $cur2     = $days <= 62 ? $cur2->addDay() : $cur2->addMonth();
                }
                $trendSeries[] = ['name' => $shift->name, 'data' => $values];
            }

            return compact('compNames', 'compValues', 'trendLabels', 'trendSeries', 'palette');
        });
    }
}
