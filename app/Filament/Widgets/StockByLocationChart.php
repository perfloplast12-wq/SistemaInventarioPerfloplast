<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockByLocationChart extends Widget
{
    protected static string $view = 'filament.widgets.stock-by-location-chart';
    protected static ?int $sort = 4;

    public ?string $selectedLocation = null;

    public function openDetail(string $locationName): void
    {
        $this->selectedLocation = $locationName;
        $this->dispatch('open-modal', id: 'stock-detail-modal');
    }

    public function getChartData(): array
    {
        $warehouseStock = Stock::whereNotNull('warehouse_id')
            ->select('warehouse_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('warehouse_id')
            ->get()
            ->map(function($s) {
                $w = Warehouse::withTrashed()->find($s->warehouse_id);
                return [
                    'name' => $w?->name ?? 'Bodega Desconocida',
                    'total' => (float)$s->total
                ];
            })
            ->groupBy('name')
            ->map(fn($group) => $group->sum('total'));

        $truckStock = Stock::whereNotNull('truck_id')
            ->select('truck_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('truck_id')
            ->get()
            ->map(function($s) {
                $t = \App\Models\Truck::withTrashed()->find($s->truck_id);
                return [
                    'name' => $t?->name ?? ($t?->plate ?? 'Camión Desconocido'),
                    'total' => (float)$s->total
                ];
            })
            ->groupBy('name')
            ->map(fn($group) => $group->sum('total'));

        $labels = array_merge($warehouseStock->keys()->toArray(), $truckStock->keys()->toArray());
        $values = array_merge($warehouseStock->values()->toArray(), $truckStock->values()->toArray());

        return compact('labels', 'values');
    }
}
