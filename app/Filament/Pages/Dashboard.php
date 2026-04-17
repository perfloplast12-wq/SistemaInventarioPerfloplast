<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Sale;
use App\Models\Production;
use App\Models\Stock;
use App\Models\Order;
use App\Models\Dispatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Custom Dashboard Page
 * We avoided InteractsWithPageFilters to prevent "Cannot mutate reactive prop" error in Livewire 3
 * while maintaining manual filter management for Power BI style slicers.
 */
class Dashboard extends Page
{
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Dashboard Gerencial';
    protected static ?string $navigationLabel = 'Escritorio';
    protected static ?int $navigationSort = -2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public array $filters = [];
    public string $activePeriod = 'today';
    public bool $showCustom = false;
    public ?string $customStart = null;
    public ?string $customEnd = null;

    public function mount(): void
    {
        $this->setPeriod('this_month');
        $this->customStart = $this->filters['startDate'];
        $this->customEnd   = $this->filters['endDate'];
    }

    public function setPeriod(string $p): void
    {
        $this->activePeriod = $p;
        $this->showCustom   = ($p === 'custom');

        $start = now();
        $end   = now();

        switch ($p) {
            case 'today':     $start = now()->startOfDay(); $end = now()->endOfDay(); break;
            case 'yesterday': $start = now()->subDay()->startOfDay(); $end = now()->subDay()->endOfDay(); break;
            case 'this_week':  $start = now()->startOfWeek(); $end = now()->endOfWeek(); break;
            case 'this_month': $start = now()->startOfMonth(); $end = now()->endOfMonth(); break;
            case 'this_year':  $start = now()->startOfYear(); $end = now()->endOfYear(); break;
            case 'custom':     return; 
        }

        $this->filters['startDate'] = $start->format('Y-m-d');
        $this->filters['endDate']   = $end->format('Y-m-d');
        
        // Use Livewire dispatch to notify child widgets to refresh
        $this->dispatch('refreshCharts');
    }

    public function applyCustomDates(): void
    {
        $this->filters['startDate'] = $this->customStart;
        $this->filters['endDate']   = $this->customEnd;
        $this->dispatch('refreshCharts');
    }

    public function getStatsData(): array
    {
        $start = Carbon::parse($this->filters['startDate'] ?? now()->startOfMonth())->startOfDay();
        $end   = Carbon::parse($this->filters['endDate']   ?? now())->endOfDay();
        
        $diffInDays = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($diffInDays);
        $prevEnd = $end->copy()->subDays($diffInDays);

        $sales = (float) Sale::where('status', 'confirmed')->whereBetween('sale_date', [$start, $end])->sum('total');
        $prevSales = (float) Sale::where('status', 'confirmed')->whereBetween('sale_date', [$prevStart, $prevEnd])->sum('total');
        
        $prod  = (float) Production::where('status', 'confirmed')->whereBetween('production_date', [$start, $end])->sum('quantity');
        $prevProd  = (float) Production::where('status', 'confirmed')->whereBetween('production_date', [$prevStart, $prevEnd])->sum('quantity');

        $stocks = Stock::with('product')->get();
        $inventoryVal = $stocks->sum(fn($s) => $s->quantity * ($s->product->cost_price ?? $s->product->purchase_cost ?? 0));

        $pendingSalesVal = (float) DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'pending')
            ->sum('order_items.subtotal');

        $lowStockCount = DB::table('products')
            ->join('stocks', 'products.id', '=', 'stocks.product_id')
            ->whereNotNull('stocks.warehouse_id')
            ->groupBy('products.id')
            ->havingRaw('SUM(stocks.quantity) <= 10')
            ->get(['products.id'])
            ->count();

        $pendOrders = Order::where('status', 'pending')->count();
        $activeDisp = Dispatch::where('status', 'in_progress')->count();

        return [
            'sales'       => 'Q ' . $this->formatNumber($sales),
            'salesTrend'  => $this->calculateTrend($sales, $prevSales),
            'profit'      => 'Q ' . $this->formatNumber($sales * 0.65), 
            'profitTrend' => $this->calculateTrend($sales * 0.65, $prevSales * 0.65),
            'profitRaw'   => $sales * 0.65,
            'production'  => $this->formatNumber($prod),
            'prodTrend'   => $this->calculateTrend($prod, $prevProd),
            'inventory'   => $this->formatNumber($inventoryVal),
            'orders'      => $pendOrders,
            'efficiency'  => '100%',
            'dispatches'  => $activeDisp,
            'lowStock'    => $lowStockCount,
        ];
    }

    private function formatNumber($v): string {
        if ($v >= 1000000) return round($v/1000000, 1) . 'M';
        if ($v >= 1000) return round($v/1000, 1) . 'k';
        return number_format($v, 0);
    }

    private function calculateTrend(float $current, float $previous): array {
        if ($previous == 0) return ['value' => $current > 0 ? 100 : 0, 'dir' => $current > 0 ? 'up' : 'flat'];
        $diff = $current - $previous;
        $pct = ($diff / $previous) * 100;
        return [
            'value' => round(abs($pct), 1),
            'dir' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat')
        ];
    }

    public function getHeaderWidgets(): array { return []; }
    public function getFooterWidgets(): array { return []; }
    public function getWidgets(): array { return []; }
    public function getColumns(): int | string | array { return 1; }
}
