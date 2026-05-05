<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Sale;
use App\Models\Production;
use App\Models\Stock;
use App\Models\Order;
use App\Models\Dispatch;
use App\Models\ProductionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Custom Dashboard Page
 */
class Dashboard extends Page
{
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Dashboard Gerencial';
    protected static ?string $navigationLabel = 'Escritorio';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'INICIO Y SEGURIDAD';

    public static function canAccess(): bool
    {
        if (auth()->user()?->hasAnyRole(['production', 'sales'])) {
            return true;
        }
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()?->hasAnyRole(['production', 'sales'])) {
            return false;
        }
        return true;
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
        $user = auth()->user();

        if ($user) {
            if ($user->hasRole('sales')) {
                $this->redirect(route('filament.admin.resources.sales.index'));
                return;
            }

            if ($user->hasRole('production')) {
                $this->redirect(route('filament.admin.resources.productions.index'));
                return;
            }
        }

        $this->setPeriod('this_week');
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
        
        $prod = (float) ProductionItem::where('type', 'output')
            ->whereHas('production', function($q) use ($start, $end) {
                $q->where('status', 'confirmed')
                  ->whereBetween('production_date', [$start, $end]);
            })->sum('quantity');

        $prevProd = (float) ProductionItem::where('type', 'output')
            ->whereHas('production', function($q) use ($prevStart, $prevEnd) {
                $q->where('status', 'confirmed')
                  ->whereBetween('production_date', [$prevStart, $prevEnd]);
            })->sum('quantity');

        $inventoryVal = 0;
        $stocks = Stock::all();
        foreach($stocks as $st) {
            $val = (float)($st->product->cost_price ?? $st->product->purchase_cost ?? 0);
            $inventoryVal += ($st->quantity * $val);
        }

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
            'lowStock'    => 0,
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
