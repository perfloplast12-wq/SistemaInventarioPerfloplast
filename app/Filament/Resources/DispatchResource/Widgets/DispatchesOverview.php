<?php

namespace App\Filament\Resources\DispatchResource\Widgets;

use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;

class DispatchesOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $dispatchesToday = Dispatch::whereDate('dispatch_date', $today)->count();
        $completedMonth = Dispatch::where('status', 'delivered')->where('dispatch_date', '>=', $thisMonth)->count();
        $pendingNow = Dispatch::whereIn('status', ['pending', 'in_transit'])->count();

        return [
            Stat::make('Ruta de Hoy', $dispatchesToday . ' Viajes')
                ->description('Asignados para fecha de hoy')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
                
            Stat::make('Entregas del Mes', $completedMonth . ' Completados')
                ->description('Rendimiento mensual')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('En Cola o Tránsito', $pendingNow . ' Activos')
                ->description('Pendientes de entrega')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
