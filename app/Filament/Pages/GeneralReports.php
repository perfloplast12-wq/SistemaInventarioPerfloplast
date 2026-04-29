<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Production;
use App\Models\Order;
use App\Models\Dispatch;
use App\Models\OrderReturn;

class GeneralReports extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Reporte General';
    protected static ?string $title = 'Panel Estratégico General';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.general-reports';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.all') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->hiddenLabel()
                            ->placeholder('Inicio')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->default(now()->startOfMonth())
                            ->live(),
                        DatePicker::make('end_date')
                            ->hiddenLabel()
                            ->placeholder('Fin')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->default(now())
                            ->live(),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function getReportData(): array
    {
        $start = Carbon::parse($this->data['start_date'] ?? now())->startOfDay();
        $end = Carbon::parse($this->data['end_date'] ?? now())->endOfDay();

        // 1. Finanzas
        $sales = Sale::whereBetween('sale_date', [$start, $end])
            ->where('status', 'confirmed')
            ->get();

        $totalSales = (float) $sales->sum('total');
        $totalPaid = (float) $sales->sum('total_paid');
        $totalPending = $totalSales - $totalPaid;
        $eficienciaCobranza = $totalSales > 0 ? ($totalPaid / $totalSales) * 100 : 0;
        
        $totalCosts = (float) DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->where('sales.status', 'confirmed')
            ->sum(DB::raw('sale_items.quantity * products.cost_price'));

        $earnings = $totalSales - $totalCosts;
        $ticketPromedio = $sales->count() > 0 ? $totalSales / $sales->count() : 0;
        $margenBruto = $totalSales > 0 ? ($earnings / $totalSales) * 100 : 0;

        // 2. Gráfica - Ventas Diarias
        $dailySales = DB::table('sales')
            ->whereBetween('sale_date', [$start, $end])
            ->where('status', 'confirmed')
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 3. Top Productos
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->where('sales.status', 'confirmed')
            ->select(
                'products.name', 
                DB::raw('SUM(sale_items.quantity) as qty'), 
                DB::raw('SUM(sale_items.subtotal) as total'),
                DB::raw('SUM(sale_items.quantity * products.cost_price) as cost_total')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function($item) {
                $item->profit = $item->total - $item->cost_total;
                $item->margin_pct = $item->total > 0 ? ($item->profit / $item->total) * 100 : 0;
                return $item;
            });

        // 4. Vendedores
        $salesByUser = DB::table('sales')
            ->join('users', 'sales.created_by', '=', 'users.id')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->where('sales.status', 'confirmed')
            ->select('users.id', 'users.name', DB::raw('SUM(sales.total) as total_sales'), DB::raw('COUNT(sales.id) as count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->get()
            ->map(function($row) use ($start, $end) {
                $row->details = Sale::where('created_by', $row->id)
                    ->whereBetween('sale_date', [$start, $end])
                    ->where('status', 'confirmed')
                    ->latest()
                    ->get();
                return $row;
            });

        // 5. Pilotos
        $dispatchesByDriver = DB::table('dispatches')
            ->leftJoin('users', 'dispatches.driver_id', '=', 'users.id')
            ->whereBetween('dispatches.created_at', [$start, $end])
            ->select(
                'dispatches.driver_id',
                DB::raw('MAX(COALESCE(users.name, dispatches.driver_name, "Sin asignar")) as driver_name'), 
                DB::raw('COUNT(dispatches.id) as count')
            )
            ->groupBy('dispatches.driver_id')
            ->orderByDesc('count')
            ->get()
            ->map(function($row) use ($start, $end) {
                $query = Dispatch::whereBetween('created_at', [$start, $end]);
                if ($row->driver_id) {
                    $query->where('driver_id', $row->driver_id);
                } else {
                    $query->whereNull('driver_id');
                }
                $row->details = $query->latest()->get();
                return $row;
            });

        // 6. Producción
        $productionDetailed = DB::table('production_items')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->join('shifts', 'productions.shift_id', '=', 'shifts.id')
            ->join('products', 'production_items.product_id', '=', 'products.id')
            ->where('production_items.type', 'output')
            ->where('productions.status', 'confirmed')
            ->whereBetween('productions.production_date', [$start, $end])
            ->select(
                'shifts.name as shift_name',
                'shifts.daily_goal',
                'products.name as product_name',
                DB::raw('SUM(production_items.quantity) as total_qty'),
                DB::raw('COUNT(DISTINCT productions.id) as operations')
            )
            ->groupBy('shifts.name', 'shifts.daily_goal', 'products.name')
            ->get()
            ->map(function($row) use ($start, $end) {
                $days = max(1, Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1);
                $metaReal = $row->daily_goal * $days;
                $row->eficiencia = $metaReal > 0 ? ($row->total_qty / $metaReal) * 100 : null;
                return $row;
            });

        // 7. Gráfica - Producción Diaria
        $dailyProduction = DB::table('production_items')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->where('production_items.type', 'output')
            ->where('productions.status', 'confirmed')
            ->whereBetween('productions.production_date', [$start, $end])
            ->select(DB::raw('DATE(productions.production_date) as date'), DB::raw('SUM(production_items.quantity) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 8. Gráfica - Despachos Diarios
        $dailyDispatches = DB::table('dispatches')
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(id) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 9. Distribución de Utilidad por Producto (Donut Chart)
        $profitByProduct = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->where('sales.status', 'confirmed')
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.subtotal - (sale_items.quantity * products.cost_price)) as profit')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('profit')
            ->get();

        return [
            'totalSales' => $totalSales,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'eficienciaCobranza' => $eficienciaCobranza,
            'totalCosts' => $totalCosts,
            'earnings' => $earnings,
            'ticketPromedio' => $ticketPromedio,
            'margenBruto' => $margenBruto,
            'dailySales' => $dailySales,
            'dailyProduction' => $dailyProduction,
            'dailyDispatches' => $dailyDispatches,
            'topProducts' => $topProducts,
            'profitByProduct' => $profitByProduct,
            'salesByUser' => $salesByUser,
            'dispatchesByDriver' => $dispatchesByDriver,
            'productionDetailed' => $productionDetailed,
            'start_date' => $start->format('d/m/Y'),
            'end_date' => $end->format('d/m/Y'),
            'start_raw' => $start->format('Y-m-d'),
            'end_raw' => $end->format('Y-m-d'),
        ];
    }

    public function downloadPdf()
    {
        $data = $this->getReportData();
        $pdf = Pdf::loadView('reports.managerial', $data);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "reporte_gerencial_" . now()->format('Ymd_His') . ".pdf");
    }
}
