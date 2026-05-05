<?php
namespace App\Filament\Widgets;

use App\Models\Production;
use App\Models\Shift;
use App\Models\ProductionItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ProductionStatsOverview extends BaseWidget
{
    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();
        $monthStart = Carbon::now()->startOfMonth();

        // 1. Producido Hoy
        $todayProduced = ProductionItem::where('type', 'output')
            ->whereHas('production', function($q) use ($todayStart, $todayEnd) {
                $q->where('status', 'confirmed')
                  ->whereBetween('production_date', [$todayStart, $todayEnd]);
            })
            ->sum('quantity');

        // 2. Producciones en Borrador
        $draftCount = Production::where('status', 'draft')->count();

        // 3. Eficiencia estimada
        $currentTime = now()->format('H:i:s');
        $activeShift = Shift::where('is_active', true)
            ->get()
            ->filter(function($s) use ($currentTime) {
                if ($s->start_time < $s->end_time) {
                    return $currentTime >= $s->start_time && $currentTime <= $s->end_time;
                }
                return $currentTime >= $s->start_time || $currentTime <= $s->end_time;
            })->first();

        $efficiencyStat = null;
        if ($activeShift && $activeShift->daily_goal > 0) {
            $realToday = (float)$todayProduced;
            $goal = (float)$activeShift->daily_goal;
            $pct = round(($realToday / $goal) * 100);
            
            $efficiencyStat = Stat::make('Eficiencia (' . $activeShift->name . ')', $pct . '%')
                ->description('Avance sobre meta diaria de ' . number_format($goal, 0) . ' unidades')
                ->descriptionIcon($pct >= 100 ? 'heroicon-m-check-badge' : 'heroicon-m-arrow-trending-up')
                ->color($pct >= 100 ? 'success' : ($pct >= 70 ? 'warning' : 'danger'))
                ->chart([$pct/2, $pct/1.5, $pct]);
        } else {
            $monthlyProduced = ProductionItem::where('type', 'output')
                ->whereHas('production', function($q) use ($monthStart) {
                    $q->where('status', 'confirmed')
                      ->whereBetween('production_date', [$monthStart, now()]);
                })
                ->sum('quantity');

            $efficiencyStat = Stat::make('Producción Mensual', number_format($monthlyProduced, 2, '.', ','))
                ->description('Acumulado de ' . Carbon::now()->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info');
        }

        return [
            Stat::make('Producido Hoy', number_format($todayProduced, 2, '.', ','))
                ->description('Unidades confirmadas hoy')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success'),
            
            $efficiencyStat,

            Stat::make('Pendientes de Confirmar', $draftCount)
                ->description($draftCount > 0 ? 'Registros en borrador' : 'Todo confirmado')
                ->descriptionIcon('heroicon-m-clock')
                ->color($draftCount > 0 ? 'warning' : 'gray'),
        ];
    }
}
