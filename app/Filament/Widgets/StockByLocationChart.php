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
            ->groupBy('warehouse_id')->with('warehouse')->get()
            ->mapWithKeys(fn($s) => [$s->warehouse?->name ?? 'Bodega' => (float)$s->total]);

        $truckStock = Stock::whereNotNull('truck_id')
            ->select('truck_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('truck_id')->with('truck')->get()
            ->mapWithKeys(fn($s) => [$s->truck?->name ?? 'Camión' => (float)$s->total]);

        $labels = array_merge($warehouseStock->keys()->toArray(), $truckStock->keys()->toArray());
        $values = array_merge($warehouseStock->values()->toArray(), $truckStock->values()->toArray());

        return compact('labels', 'values');
    }
}
