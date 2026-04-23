<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Dispatch;
use App\Models\Stock;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 2;
    }

    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        // Solo para admins/superadmins
        if (!auth()->user()?->hasRole(['super_admin', 'admin'])) {
            return [];
        }

        $todaySales = Sale::where('status', 'confirmed')
            ->whereDate('sale_date', Carbon::today())
            ->sum('total');
            
        $todayOrders = Order::whereDate('order_date', Carbon::today())->count();
        
        $activeDispatches = Dispatch::whereIn('status', ['started', 'in_transit'])->count();

        $lowStockCount = Stock::where('quantity', '<=', 10)->count(); // Umbral configurable luego
        
        $inventoryValue = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->sum(DB::raw('stocks.quantity * products.cost_price'));

        $pendingReturns = \App\Models\OrderReturn::where('status', 'pending')->count();

        return [
            Stat::make('Ventas Hoy', 'Q ' . number_format($todaySales, 2))
                ->description('Ventas confirmadas hoy')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            
            Stat::make('Pedidos Hoy', $todayOrders)
                ->description('Nuevas órdenes recibidas')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Camiones en Ruta', $activeDispatches)
                ->description('Despachos en tránsito')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Stock Bajo', $lowStockCount)
                ->description('Productos por agotarse')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
                
            Stat::make('Devoluciones', $pendingReturns)
                ->description('Devoluciones pendientes de revisar')
                ->descriptionIcon('heroicon-m-arrow-u-turn-left')
                ->color($pendingReturns > 0 ? 'danger' : 'success'),

            Stat::make('Valor de Inventario', 'Q ' . number_format($inventoryValue, 2))
                ->description('Costo total en almacén')
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color('gray'),
        ];
    }
}
