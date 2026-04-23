<?php

namespace App\Filament\Resources\SaleResource\Widgets;

use App\Models\Sale;
use App\Models\SalePayment;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;

class SalesOverview extends BaseWidget
{
    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $salesToday = Sale::where('status', 'confirmed')->whereDate('sale_date', $today)->sum('total');
        
        $salesMonth = Sale::where('status', 'confirmed')->where('sale_date', '>=', $thisMonth)->sum('total');
        $paidMonth = SalePayment::whereHas('sale', fn($q) => $q->where('status', 'confirmed')->where('sale_date', '>=', $thisMonth))->sum('amount');
        
        $totalSalesGlobal = Sale::where('status', 'confirmed')->sum('total');
        $totalPaidGlobal = SalePayment::whereHas('sale', fn($q) => $q->where('status', 'confirmed'))->sum('amount');
        $pendingTotal = $totalSalesGlobal - $totalPaidGlobal;

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
    }
}
