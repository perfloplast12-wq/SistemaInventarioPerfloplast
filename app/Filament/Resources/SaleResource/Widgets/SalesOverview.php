<?php

namespace App\Filament\Resources\SaleResource\Widgets;

use App\Models\Sale;
use App\Models\SalePayment;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SalesOverview extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = '120s';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        return Cache::remember('sales_overview_stats', 120, function () {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();

            $salesToday = Sale::where('status', 'confirmed')->whereDate('sale_date', $today)->sum('total');
            
            $salesMonth = Sale::where('status', 'confirmed')->where('sale_date', '>=', $thisMonth)->sum('total');
            $paidMonth = SalePayment::whereHas('sale', fn($q) => $q->where('status', 'confirmed')->where('sale_date', '>=', $thisMonth))->sum('amount');
            
            // Optimizar: usar una sola consulta SQL en vez de cargar todos los registros en memoria
            $pendingTotal = Sale::where('status', 'confirmed')
                ->selectRaw('SUM(total - COALESCE((SELECT SUM(amount) FROM sale_payments WHERE sale_payments.sale_id = sales.id), 0)) as pending')
                ->value('pending') ?? 0;
            $pendingTotal = max(0, $pendingTotal);

            return [
                Stat::make('Ventas Hoy', 'Q ' . number_format($salesToday, 2))
                    ->description('Ingresos confirmados hoy')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),
                    
                Stat::make('Ventas del Mes', 'Q ' . number_format($salesMonth, 2))
                    ->description('Q ' . number_format($paidMonth, 2) . ' Cobrado')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('primary'),
                    
                Stat::make('Cuentas por Cobrar', 'Q ' . number_format($pendingTotal, 2))
                    ->description('Saldo pendiente general')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),
            ];
        });
    }
}
