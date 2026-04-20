<?php

namespace App\Filament\Pages;

use App\Filament\Resources\FinishedProductResource;
use App\Filament\Resources\InventoryMovementResource;
use App\Filament\Resources\RawMaterialProductResource;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Inventario extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Inventario';
    protected static ?string $title = 'Inventario';
    protected static ?int $navigationSort = 20;

    protected static ?string $navigationGroup = 'Operación';

    protected static string $view = 'filament.pages.inventario';

    public int $totalProducts = 0;
    public int $rawMaterials = 0;
    public int $finishedProducts = 0;
    public int $criticalStockCount = 0;
    public int $pendingOrdersCount = 0;
    public int $last24hMovements = 0;
    public float $riskIndex = 0; // % de productos en riesgo

    public function mount(): void
    {
        // Contamos la variedad (SKUs únicos)
        $this->totalProducts = Product::query()->count();
        $this->rawMaterials = Product::query()->where('type', 'raw_material')->count();
        $this->finishedProducts = Product::query()->where('type', 'finished_product')->count();


        // Stock Crítico (Sincronizado <= 10 Presentaciones en Bodega)
        $this->criticalStockCount = Product::query()
            ->withSum(['stocks' => fn($q) => $q->whereNotNull('warehouse_id')], 'quantity')
            ->get()
            ->filter(fn($p) => $p->convertToPresentationUnit((float)($p->stocks_sum_quantity ?? 0)) <= 10)
            ->count();

        // Pedidos Pendientes (Administrativo)
        $this->pendingOrdersCount = \App\Models\Order::where('status', 'pending')->count();

        // Actividad 24h
        $this->last24hMovements = \App\Models\InventoryMovement::query()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        // Índice de Riesgo (%)
        if ($this->totalProducts > 0) {
            $this->riskIndex = ($this->criticalStockCount / $this->totalProducts) * 100;
        }
    }

    public function getProductsData(string $type)
    {
        $products = Product::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->map(function ($product) {
                return [
                    'name' => $product->name,
                    'stock' => (float)$product->stocks->sum('quantity'),
                ];
            })
            ->sortByDesc('stock')
            ->take(10);

        $maxStock = $products->max('stock') ?: 1;

        return [
            'items' => $products,
            'max_stock' => $maxStock,
        ];
    }

    public static function canAccess(): bool
    {
        if (auth()->user()?->hasRole('production')) {
            return false;
        }
        return auth()->user()?->can('inventory.view') ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getRawMaterialsIndexUrl(): string
    {
        return RawMaterialProductResource::getUrl('index');
    }

    public function getFinishedProductsIndexUrl(): string
    {
        return FinishedProductResource::getUrl('index');
    }




    public function getTotalProductsCount(): int
    {
        return $this->totalProducts ?: Product::query()->count();
    }

    public function getRawMaterialsCount(): int
    {
        return $this->rawMaterials ?: Product::query()->where('type', 'raw_material')->count();
    }

    public function getFinishedProductsCount(): int
    {
        return $this->finishedProducts ?: Product::query()->where('type', 'finished_product')->count();
    }

    // Kardex (historial)
    public function getKardexUrl(): string
    {
        return InventoryMovementResource::getUrl('index');
    }

    public function getReturnsUrl(): string
    {
        return \App\Filament\Resources\OrderReturnResource::getUrl('index');
    }

    // Crear movimiento con tipo ya seleccionado
    public function getMovementCreateUrl(string $type): string
    {
        return InventoryMovementResource::getUrl('create') . '?type=' . urlencode($type);
    }

    public function getMovementsUrl(string $type): string
    {
        return InventoryMovementResource::getUrl('index') . '?type=' . urlencode($type); 
    }

    public function getWarehouseSummaries(): array
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $result = [];

        foreach ($warehouses as $warehouse) {
            // Totales
            $rawTotal = Stock::where('warehouse_id', $warehouse->id)
                ->where('quantity', '>', 0)
                ->whereHas('product', fn ($q) => $q->where('type', 'raw_material'))
                ->sum('quantity');

            $finishedTotal = Stock::where('warehouse_id', $warehouse->id)
                ->where('quantity', '>', 0)
                ->whereHas('product', fn ($q) => $q->where('type', 'finished_product'))
                ->sum('quantity');

            // Top Products for Quick View
            $topProducts = Stock::where('warehouse_id', $warehouse->id)
                ->where('quantity', '>', 0)
                ->whereHas('product') // Only active products
                ->with(['product.unitOfMeasure'])
                ->get()
                ->sortByDesc('quantity')
                ->take(3)
                ->map(fn($s) => [
                    'name' => $s->product?->name ?? 'N/A',
                    'qty' => $s->quantity,
                    'unit' => $s->product?->unitOfMeasure?->abbreviation ?? 'u'
                ]);

            $result[] = [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'is_factory' => $warehouse->is_factory,
                'raw_total' => (float) $rawTotal,
                'finished_total' => (float) $finishedTotal,
                'top_items' => $topProducts,
            ];
        }

        return $result;
    }

    public function getRecentMovements(): \Illuminate\Support\Collection
    {
        $typeMap = [
            'in'       => ['label' => 'Entrada',  'color' => '#10b981', 'bg' => '#d1fae5'],
            'out'      => ['label' => 'Salida',   'color' => '#f43f5e', 'bg' => '#ffe4e6'],
            'transfer' => ['label' => 'Traslado', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
            'adjust'   => ['label' => 'Ajuste',   'color' => '#f59e0b', 'bg' => '#fef3c7'],
        ];

        return \App\Models\InventoryMovement::query()
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'creator'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($m) use ($typeMap) {
                $type    = $typeMap[$m->type] ?? ['label' => ucfirst($m->type), 'color' => '#64748b', 'bg' => '#f1f5f9'];
                $product = $m->product?->name ?? 'Producto';
                $qty     = number_format((float)$m->quantity, 0);
                $unit    = $m->product?->unitOfMeasure?->abbreviation ?? 'u';
                // Build description based on type
                $desc = match($m->type) {
                    'in'       => "Ingreso de {$qty}{$unit} de {$product}",
                    'out'      => "Salida de {$qty}{$unit} de {$product}",
                    'transfer' => "Traslado de {$product}" . ($m->fromWarehouse ? " de {$m->fromWarehouse->name}" : '') . ($m->toWarehouse ? " a {$m->toWarehouse->name}" : ''),
                    'adjust'   => "Ajuste de inventario — {$product}",
                    default    => "{$type['label']} de {$product}",
                };

                return [
                    'type_label' => $type['label'],
                    'type_color' => $type['color'],
                    'type_bg'    => $type['bg'],
                    'description'=> $desc,
                    'user'       => $m->creator?->name ?? 'Sistema',
                    'time_ago'   => $m->created_at->diffForHumans(),
                    'qty'        => $qty,
                    'unit'       => $unit,
                ];
            });
    }

    public function getCriticalStockProducts(): \Illuminate\Support\Collection
    {
        return Product::query()
            ->with(['stocks' => fn($q) => $q->whereNotNull('warehouse_id'), 'unitOfMeasure'])
            ->withSum(['stocks' => fn($q) => $q->whereNotNull('warehouse_id')], 'quantity')
            ->get()
            ->filter(function($p) {
                return $p->convertToPresentationUnit((float)($p->stocks_sum_quantity ?? 0)) <= 10;
            })
            ->take(5)
            ->map(function ($p) {
                // Cantidad en presentaciones
                $qty = $p->convertToPresentationUnit((float)($p->stocks_sum_quantity ?? 0));
                return [
                    'name' => $p->name,
                    'stock' => (float) $qty,
                    'unit' => $p->presentationUnit?->abbreviation ?: ($p->unitOfMeasure?->abbreviation ?: 'u'),
                ];
            });
    }
}