<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Dispatch;
use App\Models\Truck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogisticsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.logistics-widget';
    protected static ?int $sort = 8;

    public function getChartData(): array
    {
        $filters = $this->filters ?? [];
        $start   = Carbon::parse($filters['startDate'] ?? now()->startOfMonth())->startOfDay();
        $end     = Carbon::parse($filters['endDate']   ?? now())->endOfDay();

        $trucks = Truck::where('is_active', true)->get();

        if ($trucks->isEmpty()) {
            return ['names' => [], 'trips' => []];
        }

        $dispatches = Dispatch::query()
            ->whereBetween('created_at', [$start, $end])
            ->select('truck_id', DB::raw('COUNT(id) as trips'))
            ->groupBy('truck_id')->pluck('trips', 'truck_id');

        $names = $trips = [];
        foreach ($trucks as $truck) {
            $names[] = $truck->name;
            $trips[] = (int)($dispatches[$truck->id] ?? 0);
        }

        return compact('names', 'trips');
    }
}
