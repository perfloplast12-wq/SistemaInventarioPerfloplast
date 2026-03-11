<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;

class OrdersOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();

        // Pedidos del mes
        $ordersMonth = Order::where('order_date', '>=', $thisMonth)->count();
        $totalCost = \App\Models\OrderItem::whereHas('order', function ($query) use ($thisMonth) {
            $query->where('order_date', '>=', $thisMonth);
        })->sum('subtotal');
        
        // Pedidos Pendientes de Recibir
        $pending = Order::where('status', 'pending')->count();

        return [
            Stat::make('Pedidos del Mes', $ordersMonth . ' Órdenes')
                ->description('Volumen mensual')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
                
            Stat::make('Gasto Estimado Mensual', 'Q ' . number_format($totalCost, 2))
                ->description('Inversión en pedidos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Pedidos Pendientes', $pending . ' Activos')
                ->description('Esperando recepción')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
