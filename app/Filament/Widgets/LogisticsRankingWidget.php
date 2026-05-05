<?php

namespace App\Filament\Widgets;

use App\Models\Dispatch;
use App\Models\User;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LogisticsRankingWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;
    protected static string $view = 'filament.widgets.logistics-ranking-widget';
    protected int|string|array $columnSpan = 'half';

    public function getOperatorsData(): array
    {
        $filters = $this->filters ?? [];
        $start = Carbon::parse($filters['startDate'] ?? now()->startOfMonth())->startOfDay();
        $end = Carbon::parse($filters['endDate'] ?? now())->endOfDay();

        // Obtener pilotos (driver_id) y su conteo de despachos (solo finalizados, según título)
        $data = Dispatch::query()
            ->whereBetween('dispatch_date', [$start, $end])
            ->whereIn('status', ['completed', 'delivered'])
            ->select('driver_id', DB::raw('COUNT(id) as total_dispatches'))
            ->groupBy('driver_id')
            ->orderByDesc('total_dispatches')
            ->with('driver')
            ->get();

        if ($data->isEmpty()) {
            return [];
        }

        $max = $data->max('total_dispatches') ?: 1;

        return $data->map(function ($item) use ($max) {
            $lastDispatch = Dispatch::where('driver_id', $item->driver_id)
                ->latest('dispatch_date')
                ->first();

            return [
                'name' => $item->driver?->name ?? 'Piloto Desconocido',
                'dispatches' => $item->total_dispatches,
                'status' => $lastDispatch && $lastDispatch->status === 'in_progress' ? 'En Ruta' : 'Disponible',
                'last_date' => $lastDispatch ? $lastDispatch->dispatch_date->diffForHumans() : 'Sin actividad',
                'performance' => round(($item->total_dispatches / $max) * 100),
                'avatar' => $item->driver?->profile_photo_url ? '/storage/'.$item->driver->profile_photo_url : 'https://ui-avatars.com/api/?name=' . urlencode($item->driver?->name ?? 'P') . '&color=4F46E5&background=EEF2FF&bold=true',
            ];
        })->toArray();
    }
}
