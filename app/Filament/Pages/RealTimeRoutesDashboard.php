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
    protected ?string $maxContentWidth = 'full';
    protected static ?int $navigationSort = 1;

    public string $activeTab = 'todos';
    public ?int $selectedDispatchId = null;

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
        // Si hay un despacho en la URL, seleccionarlo por defecto
        if (request()->query('dispatch')) {
            $this->selectedDispatchId = (int) request()->query('dispatch');
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

        return $query
            ->latest('dispatch_date')
            ->limit(100)
            ->get()
            ->map(function ($d) {
            $totalOrders = $d->orders->count();
            $completedOrders = $d->orders->whereIn('status', ['completed', 'completed_with_return'])->count();
            $progress = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;
            
            return [
                'id' => $d->id,
                'dispatch_number' => $d->dispatch_number,
                'driver_name' => $d->driver?->name ?? $d->driver_name ?? 'Sin Piloto',
                'truck_name' => $d->truck?->name ?? 'Sin Camión',
                'status' => $d->status,
                'progress' => $progress,
                'route' => $d->route,
                'total_value' => $d->total_value,
            ];
        })->toArray();
    }

    /**
     * Cambiar de pestaña
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedDispatchId = null;

        $this->dispatch(
            'dashboard-filter-changed',
            pilots: $this->getActivePilotsLocations(),
        );
    }

    /**
     * Seleccionar un piloto/despacho en particular
     */
    public function selectDispatch(?int $dispatchId): void
    {
        $this->selectedDispatchId = $dispatchId;
        
        if ($dispatchId) {
            $this->dispatch(
                'dispatch-selected',
                dispatchId: $dispatchId,
                details: $this->getSelectedDispatchDetails(),
                locations: $this->getSelectedDispatchLocations(),
                stops: $this->getSelectedDispatchStops(),
            );
        }
    }

    /**
     * Obtener los detalles del despacho seleccionado
     */
    public function getSelectedDispatchDetails(): ?array
    {
        if (!$this->selectedDispatchId) return null;

        $d = Dispatch::with(['driver', 'truck', 'orders.items'])->find($this->selectedDispatchId);
        if (!$d) return null;

        $totalOrders = $d->orders->count();
        $completedOrders = $d->orders->whereIn('status', ['completed', 'completed_with_return'])->count();
        $pendingOrders = $d->orders
            ->whereNotIn('status', ['completed', 'completed_with_return', 'returned'])
            ->count();
        $returnsCount = OrderReturn::where('dispatch_id', $d->id)->count();
        $progress = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;

        return [
            'id' => $d->id,
            'dispatch_number' => $d->dispatch_number,
            'driver_initials' => strtoupper(substr($d->driver?->name ?? 'X', 0, 2)),
            'driver_name' => $d->driver?->name ?? $d->driver_name ?? 'Sin Piloto',
            'truck_name' => $d->truck?->name ?? 'Sin Camión',
            'status' => $d->status,
            'route' => $d->route,
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
     * Obtener las coordenadas del recorrido del despacho seleccionado
     */
    public function getSelectedDispatchLocations(): array
    {
        if (!$this->selectedDispatchId) return [];

        return \App\Models\DispatchLocation::where('dispatch_id', $this->selectedDispatchId)
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
     * Obtener las paradas (pedidos) del despacho seleccionado
     */
    public function getSelectedDispatchStops(): array
    {
        if (!$this->selectedDispatchId) return [];

        $d = Dispatch::find($this->selectedDispatchId);
        if (!$d) return [];

        return $d->orders()
            ->with(['items.product', 'items.color'])
            ->get()
            ->map(function ($order, $index) {
                return [
                    'id' => $order->id,
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
                    ])->toArray()
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

        $locations = [];
        foreach ($dispatches as $d) {
            $lastLoc = \App\Models\DispatchLocation::where('dispatch_id', $d->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastLoc) {
                $locations[] = [
                    'dispatch_id' => $d->id,
                    'driver_name' => $d->driver?->name ?? $d->driver_name ?? 'Sin Piloto',
                    'truck_name' => $d->truck?->name ?? 'Sin Camión',
                    'status' => $d->status,
                    'route' => $d->route,
                    'lat' => (float) $lastLoc->lat,
                    'lng' => (float) $lastLoc->lng,
                    'updated_at' => $lastLoc->created_at->diffForHumans(),
                    'speed' => $lastLoc->speed ?? 0,
                    'source' => 'gps',
                ];
            }
        }
        return $locations;
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
                }
            });

            Notification::make()
                ->title('Parada Completada')
                ->body('El pedido se ha marcado como Entregado.')
                ->success()
                ->send();

            $this->selectDispatch($this->selectedDispatchId);
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
            });

            Notification::make()
                ->title('Devolución Registrada')
                ->body('Se ha reportado la devolución con éxito.')
                ->success()
                ->send();

            $this->dispatch('close-return-modal');
            $this->selectDispatch($this->selectedDispatchId);
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

            $this->selectDispatch($this->selectedDispatchId);
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
            'selectedLocations' => $this->getSelectedDispatchLocations(),
        ];
    }
}
