<?php

namespace App\Filament\Resources\DispatchResource\Widgets;

use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DispatchesOverview extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = '120s';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        return Cache::remember('dispatches_overview_stats_v2', 120, function () {
            $totalDispatches = Dispatch::count();
            $completed = Dispatch::whereIn('status', ['completed', 'delivered'])->count();
            $activeNow = Dispatch::whereIn('status', ['pending', 'in_progress'])->count();

            return [
                Stat::make('Despachos', $totalDispatches . ' Viajes')
                    ->description('Registros visibles en la tabla')
                    ->descriptionIcon('heroicon-m-truck')
                    ->color('primary'),

                Stat::make('Entregas', $completed . ' Completados')
                    ->description('Completados o entregados')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('En Cola o Transito', $activeNow . ' Activos')
                    ->description('Pendientes o en proceso')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),
            ];
        });
    }
}
