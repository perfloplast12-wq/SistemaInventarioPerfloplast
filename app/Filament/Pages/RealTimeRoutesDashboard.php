<?php

namespace App\Filament\Pages;

use App\Models\Dispatch;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Color;
use App\Services\DispatchService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RealTimeRoutesDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'LOGÍSTICA';
    protected static ?string $navigationLabel = 'Despachos';
    protected static ?string $title = 'Despachos';
    protected static ?string $slug = 'dispatch-map';
    protected static string $view = 'filament.pages.real-time-routes-dashboard';
    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }
    protected static ?int $navigationSort = 1;

    public string $activeTab = 'todos';
    public ?int $selectedDispatchId = null;
    public ?int $selectedDriverId = null;

    // Campos para el modal de Devolución
    public ?int $returnOrderId = null;
    public ?int $returnProductId = null;
    public ?int $returnColorId = null;
    public float $returnQuantity = 0;
    public string $returnReason = '';
    public string $returnNotes = '';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('dispatches.view') ?? false;
    }

    public function mount(): void
    {
        if (request()->query('dispatch')) {
            $dispatchId = (int) request()->query('dispatch');
            $dispatch = Dispatch::find($dispatchId);
            if ($dispatch) {
                $this->selectedDispatchId = $dispatchId;
                $this->selectedDriverId = $dispatch->driver_id;
            }

            return;
        }

        $defaultDispatch = $this->dispatchesQuery()
            ->whereNotNull('driver_id')
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->latest('dispatch_date')
            ->first();

        if ($defaultDispatch) {
            $this->selectedDispatchId = $defaultDispatch->id;
            $this->selectedDriverId = $defaultDispatch->driver_id;
        }
    }

    /**
     * Obtener estadísticas de los despachos para los badges superiores
     */
    public function getTabsStats(): array
    {
        $baseQuery = $this->dispatchesQuery();

        return [
            'todos' => (clone $baseQuery)->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'delivered' => (clone $baseQuery)->where('status', 'delivered')->count(),
        ];
    }

    /**
     * Obtener los despachos filtrados por pestaña
     */
    public function getDispatches(): array
    {
        $query = $this->dispatchesQuery()
            ->with(['driver', 'truck', 'orders']);

        if ($this->activeTab !== 'todos') {
            $query->where('status', $this->activeTab);
        }

        $grouped = $query
            ->latest('dispatch_date')
            ->limit(100)
            ->get()
            ->groupBy(fn ($d) => $d->driver_id ?? strtolower(trim($d->driver_name ?? '')));

        return $grouped
            ->map(function ($dispatches) {
                $first = $dispatches->first();
                $driverName = $first->driver?->name ?? $first->driver_name ?? 'Sin Piloto';

                $truckNames = $dispatches->pluck('truck.name')->filter()->unique()->values()->toArray();
                $truckName = count($truckNames) === 1 ? $truckNames[0] : implode(', ', array_slice($truckNames, 0, 2));
                if (count($truckNames) > 1) {
                    $truckName .= ' +';
                }

                $routes = $dispatches->pluck('route')->filter()->unique()->values()->toArray();
                $route = count($routes) === 1 ? $routes[0] : implode(', ', array_slice($routes, 0, 2));
                if (count($routes) > 2) {
                    $route .= ' +';
                }

                $allOrders = $dispatches->flatMap(fn ($d) => $d->orders);
                $totalOrders = $allOrders->count();
                $completedOrders = $allOrders->whereIn('status', ['completed', 'completed_with_return'])->count();
                $progress = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;

                return [
                    'driver_id' => $first->driver_id ?? null,
                    'id' => $first->id,
                    'dispatch_number' => $first->dispatch_number,
                    'driver_name' => $driverName,
                    'truck_name' => $truckName ?: 'Sin Camión',
                    'status' => $dispatches->pluck('status')->contains('in_progress') ? 'in_progress' : ($dispatches->pluck('status')->contains('pending') ? 'pending' : 'completed'),
                    'progress' => $progress,
                    'route' => $route ?: 'Sin ruta',
                    'dispatch_count' => $dispatches->count(),
                    'total_orders' => $totalOrders,
                    'completed_orders' => $completedOrders,
                    'pending_orders' => $totalOrders - $completedOrders,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Cambiar de pestaña
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedDispatchId = null;
        $this->selectedDriverId = null;

        $this->dispatch(
            'dashboard-filter-changed',
            pilots: $this->getActivePilotsLocations(),
        );
    }

    /**
     * Seleccionar un piloto en particular
     */
    public function selectDriver(int $driverId): void
    {
        $this->selectedDriverId = $driverId;
        $this->selectedDispatchId = $this->getDriverLatestDispatchId($driverId);

        $this->dispatch(
            'dispatch-selected',
            driverId: $driverId,
            details: $this->getSelectedDriverDetails(),
            locations: $this->getSelectedDriverLocations(),
            stops: $this->getSelectedDriverStops(),
        );
    }

    /**
     * Obtener los detalles del piloto seleccionado (todos los despachos del mismo conductor)
     */
    public function getSelectedDriverDetails(): ?array
    {
        if (!$this->selectedDriverId) return null;

        $dispatches = $this->dispatchesQuery()
            ->when($this->activeTab !== 'todos', fn (Builder $query) => $query->where('status', $this->activeTab))
            ->with(['driver', 'truck', 'orders'])
            ->where('driver_id', $this->selectedDriverId)
            ->get();

        if ($dispatches->isEmpty()) {
            return null;
        }

        $driverName = $dispatches->first()->driver?->name ?? $dispatches->first()->driver_name ?? 'Sin Piloto';
        $driverInitials = strtoupper(substr($driverName, 0, 2));

        $truckNames = $dispatches->pluck('truck.name')->filter()->unique()->values()->toArray();
        $truckName = count($truckNames) === 1 ? $truckNames[0] : implode(', ', array_slice($truckNames, 0, 2));
        if (count($truckNames) > 1) {
            $truckName .= ' +';
        }

        $routes = $dispatches->pluck('route')->filter()->unique()->values()->toArray();
        $route = count($routes) === 1 ? $routes[0] : implode(', ', array_slice($routes, 0, 2));
        if (count($routes) > 2) {
            $route .= ' +';
        }

        $allOrders = $dispatches->flatMap(fn($d) => $d->orders);
        $totalOrders = $allOrders->count();
        $completedOrders = $allOrders->whereIn('status', ['completed', 'completed_with_return'])->count();
        $pendingOrders = $allOrders->whereNotIn('status', ['completed', 'completed_with_return', 'returned'])->count();
        $returnsCount = OrderReturn::whereIn('dispatch_id', $dispatches->pluck('id'))->count();

        $status = $dispatches->pluck('status')->contains('in_progress')
            ? 'in_progress'
            : ($dispatches->pluck('status')->contains('pending') ? 'pending' : 'completed');

        $progress = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;

        $dispatchIds = $dispatches->pluck('id')->unique()->values()->toArray();
        $latestDispatchId = $dispatches->sortByDesc('dispatch_date')->first()->id;

        return [
            'driver_id' => $this->selectedDriverId,
            'latest_dispatch_id' => $latestDispatchId,
            'dispatch_ids' => $dispatchIds,
            'dispatch_count' => $dispatches->count(),
            'driver_initials' => $driverInitials,
            'driver_name' => $driverName,
            'truck_name' => $truckName ?: 'Sin Camión',
            'status' => $status,
            'route' => $route ?: 'Sin ruta',
            'stats' => [
                'total' => $totalOrders,
                'completed' => $completedOrders,
                'pending' => $pendingOrders,
                'returns' => $returnsCount,
            ],
            'progress' => $progress,
        ];
    }

    /**
     * Obtener las coordenadas del recorrido del piloto seleccionado
     */
    public function getSelectedDriverLocations(): array
    {
        if (!$this->selectedDriverId) return [];

        $dispatches = $this->dispatchesQuery()
            ->when($this->activeTab !== 'todos', fn (Builder $query) => $query->where('status', $this->activeTab))
            ->with([])
            ->where('driver_id', $this->selectedDriverId)
            ->get();

        $bestDispatch = null;
        $bestLocationTime = null;

        foreach ($dispatches as $dispatch) {
            $lastLoc = \App\Models\DispatchLocation::where('dispatch_id', $dispatch->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastLoc) {
                continue;
            }

            if (!$bestLocationTime || $lastLoc->created_at > $bestLocationTime) {
                $bestLocationTime = $lastLoc->created_at;
                $bestDispatch = $dispatch;
            }
        }

        if (!$bestDispatch) {
            return [];
        }

        return \App\Models\DispatchLocation::where('dispatch_id', $bestDispatch->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($loc) => [
                'lat' => (float) $loc->lat,
                'lng' => (float) $loc->lng,
                'created_at' => $loc->created_at->format('H:i:s'),
            ])
            ->toArray();
    }

    /**
     * Obtener las paradas (pedidos) de todos los despachos del piloto seleccionado
     */
    public function getSelectedDriverStops(): array
    {
        if (!$this->selectedDriverId) return [];

        $dispatchIds = $this->dispatchesQuery()
            ->when($this->activeTab !== 'todos', fn (Builder $query) => $query->where('status', $this->activeTab))
            ->where('driver_id', $this->selectedDriverId)
            ->pluck('id')
            ->toArray();

        if (empty($dispatchIds)) {
            return [];
        }

        return Order::with(['items.product', 'items.color'])
            ->whereIn('dispatch_id', $dispatchIds)
            ->get()
            ->map(function ($order, $index) {
                return [
                    'id' => $order->id,
                    'dispatch_id' => $order->dispatch_id,
                    'number' => $index + 1,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'delivery_address' => $order->delivery_address,
                    'status' => $order->status,
                    'lat' => (float) $order->lat,
                    'lng' => (float) $order->lng,
                    'total' => $order->total,
                    'phone' => $order->phone,
                    'items' => $order->items->map(fn($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'color_id' => $item->color_id,
                        'product_name' => $item->product?->name ?? 'Producto',
                        'color_name' => $item->color?->name ?? 'Sin Color',
                        'quantity' => $item->quantity,
                        'subtotal' => $item->subtotal,
                    ])->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Obtener la ubicación en tiempo real de todos los pilotos activos (en progreso)
     */
    public function getActivePilotsLocations(): array
    {
        $dispatches = $this->dispatchesQuery()
            ->when($this->activeTab !== 'todos', fn (Builder $query) => $query->where('status', $this->activeTab))
            ->with(['driver', 'truck', 'orders'])
            ->latest('dispatch_date')
            ->limit(100)
            ->get();

        $rows = [];
        foreach ($dispatches as $d) {
            $lastLoc = \App\Models\DispatchLocation::where('dispatch_id', $d->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastLoc) {
                continue;
            }

            $rows[] = [
                'driver_id' => $d->driver_id ?? null,
                'driver_key' => $d->driver_id ?? strtolower(trim($d->driver_name ?? '')),
                'dispatch_id' => $d->id,
                'driver_name' => $d->driver?->name ?? $d->driver_name ?? 'Sin Piloto',
                'truck_name' => $d->truck?->name ?? 'Sin Camión',
                'status' => $d->status,
                'route' => $d->route,
                'lat' => (float) $lastLoc->lat,
                'lng' => (float) $lastLoc->lng,
                'timestamp' => $lastLoc->created_at->timestamp,
                'updated_at' => $lastLoc->created_at->diffForHumans(),
                'speed' => $lastLoc->speed ?? 0,
                'total_orders' => $d->orders->count(),
                'completed_orders' => $d->orders->whereIn('status', ['completed', 'completed_with_return'])->count(),
                'pending_orders' => $d->orders->whereNotIn('status', ['completed', 'completed_with_return', 'returned'])->count(),
                'dispatch_count' => 1,
            ];
        }

        return collect($rows)
            ->groupBy('driver_key')
            ->map(function ($items) {
                $latest = $items->sortByDesc('timestamp')->first();
                $truckNames = $items->pluck('truck_name')->filter()->unique()->values()->toArray();
                $truckName = count($truckNames) === 1 ? $truckNames[0] : implode(', ', array_slice($truckNames, 0, 2));
                if (count($truckNames) > 1) {
                    $truckName .= ' +';
                }

                return [
                    'driver_id' => $latest['driver_id'],
                    'driver_name' => $latest['driver_name'],
                    'truck_name' => $truckName ?: 'Sin Camión',
                    'route' => $latest['route'] ?: 'Sin ruta',
                    'status' => $items->pluck('status')->contains('in_progress') ? 'in_progress' : ($items->pluck('status')->contains('pending') ? 'pending' : 'completed'),
                    'lat' => $latest['lat'],
                    'lng' => $latest['lng'],
                    'updated_at' => $latest['updated_at'],
                    'speed' => $latest['speed'],
                    'dispatch_ids' => $items->pluck('dispatch_id')->unique()->values()->toArray(),
                    'total_orders' => $items->sum('total_orders'),
                    'completed_orders' => $items->sum('completed_orders'),
                    'pending_orders' => $items->sum('pending_orders'),
                    'dispatch_count' => $items->sum('dispatch_count'),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getDriverLatestDispatchId(int $driverId): ?int
    {
        return $this->dispatchesQuery()
            ->when($this->activeTab !== 'todos', fn (Builder $query) => $query->where('status', $this->activeTab))
            ->where('driver_id', $driverId)
            ->latest('dispatch_date')
            ->value('id');
    }

    protected function dispatchesQuery(): Builder
    {
        $query = Dispatch::query();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('conductor')) {
            $query->where('driver_id', $user->id);
        }

        if ($user->hasRole('sales')) {
            $query->whereHas('orders', fn (Builder $q) => $q->where('created_by', $user->id));
        }

        return $query;
    }

    /**
     * Completar una parada individual (Entregar Pedido)
     */
    public function completeOrder(int $orderId): void
    {
        try {
            DB::transaction(function () use ($orderId) {
                $order = Order::find($orderId);
                if (!$order) throw new \Exception('Pedido no encontrado.');
                
                $order->update(['status' => 'completed']);
                
                // Recalcular y refrescar
                if ($order->dispatch_id) {
                    $order->dispatch->recalculateTotals();
                    $this->syncDispatchCompletionFromOrders($order->dispatch);
                }
            });

            Notification::make()
                ->title('Parada Completada')
                ->body('El pedido se ha marcado como Entregado.')
                ->success()
                ->send();

            $this->syncSelectedDriverState();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Iniciar el modal para reportar devolución
     */
    public function initReturnModal(int $orderId): void
    {
        $this->returnOrderId = $orderId;
        $order = Order::with('items.product')->find($orderId);
        
        if ($order && $order->items->first()) {
            $item = $order->items->first();
            $this->returnProductId = $item->product_id;
            $this->returnColorId = $item->color_id;
            $this->returnQuantity = $item->quantity;
        }

        $this->returnReason = 'El cliente no se encontraba';
        $this->returnNotes = '';
        
        $this->dispatch('open-return-modal');
    }

    /**
     * Guardar devolución de producto y actualizar estado del pedido
     */
    public function submitReturn(): void
    {
        try {
            DB::transaction(function () {
                $order = Order::find($this->returnOrderId);
                if (!$order) throw new \Exception('Pedido no encontrado.');
                $dispatch = $order->dispatch;
                if (!$dispatch) throw new \Exception('El pedido no tiene un despacho asignado.');

                OrderReturn::create([
                    'dispatch_id' => $dispatch->id,
                    'order_id' => $order->id,
                    'product_id' => $this->returnProductId,
                    'color_id' => $this->returnColorId,
                    'driver_id' => $dispatch->driver_id,
                    'truck_id' => $dispatch->truck_id,
                    'quantity' => $this->returnQuantity,
                    'reason' => $this->returnReason,
                    'status' => 'pending', // Espera revisión de bodega
                    'notes' => $this->returnNotes,
                    'created_by' => auth()->id(),
                ]);

                // Actualizar estado del pedido a devuelto
                $order->update(['status' => 'returned']);
                $dispatch->recalculateTotals();
                $this->syncDispatchCompletionFromOrders($dispatch);
            });

            Notification::make()
                ->title('Devolución Registrada')
                ->body('Se ha reportado la devolución con éxito.')
                ->success()
                ->send();

            $this->dispatch('close-return-modal');
            $this->syncSelectedDriverState();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Finalizar el Despacho Completo (Carga entregada global)
     */
    public function finishDispatchGlobal(int $dispatchId): void
    {
        try {
            $dispatch = Dispatch::find($dispatchId);
            if (!$dispatch) throw new \Exception('Despacho no encontrado.');

            if ($dispatch->status === 'in_progress') {
                app(DispatchService::class)->complete($dispatch);
            }
            
            app(DispatchService::class)->deliver($dispatch);

            Notification::make()
                ->title('Despacho Liquidado con Éxito')
                ->body('El stock del camión se ha rebajado y se generaron las facturas correspondientes.')
                ->success()
                ->send();

            $this->selectedDriverId = null;
            $this->selectedDispatchId = null;
            $this->dispatch('dispatch-data-changed');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Cancelar despacho y devolver stock
     */
    public function cancelDispatchGlobal(int $dispatchId): void
    {
        try {
            $dispatch = Dispatch::find($dispatchId);
            if (!$dispatch) throw new \Exception('Despacho no encontrado.');

            app(DispatchService::class)->cancel($dispatch);

            Notification::make()
                ->title('Despacho Cancelado')
                ->body('El stock se ha reincorporado a la bodega origen y se eliminó el registro.')
                ->success()
                ->send();

            $this->selectedDispatchId = null;
            $this->selectedDriverId = null;
            $this->dispatch('dispatch-data-changed');
            $this->dispatch('dispatch-cancelled');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Endpoint interno para polling del mapa desde Javascript
     */
    public function refreshLocations(): array
    {
        return [
            'pilots' => $this->getActivePilotsLocations(),
            'selectedLocations' => $this->getSelectedDriverLocations(),
        ];
    }

    protected function syncSelectedDriverState(): void
    {
        $this->dispatch('dispatch-data-changed');

        if (!$this->selectedDriverId) {
            $this->dispatch(
                'dashboard-filter-changed',
                pilots: $this->getActivePilotsLocations(),
            );

            return;
        }

        $this->selectedDispatchId = $this->getDriverLatestDispatchId($this->selectedDriverId);

        $this->dispatch(
            'dispatch-selected',
            driverId: $this->selectedDriverId,
            details: $this->getSelectedDriverDetails(),
            locations: $this->getSelectedDriverLocations(),
            stops: $this->getSelectedDriverStops(),
        );
    }

    protected function syncDispatchCompletionFromOrders(Dispatch $dispatch): void
    {
        $dispatch->refresh();

        if ($dispatch->status !== 'in_progress') {
            return;
        }

        if ($dispatch->orders()->count() === 0) {
            return;
        }

        $openOrders = $dispatch->orders()
            ->whereNotIn('status', ['completed', 'completed_with_return', 'returned'])
            ->count();

        if ($openOrders === 0) {
            app(DispatchService::class)->complete($dispatch);
        }
    }
}
