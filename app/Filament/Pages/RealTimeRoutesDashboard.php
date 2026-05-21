<?php

namespace App\Filament\Pages;

use App\Models\Dispatch;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\DispatchMapDataService;
use App\Services\DispatchService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;

class RealTimeRoutesDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'LOGÍSTICA';

    protected static ?string $navigationLabel = 'Despachos';

    protected static ?string $title = 'Despachos';

    protected static ?string $slug = 'dispatch-map';

    protected static string $view = 'filament.pages.real-time-routes-dashboard';

    protected static ?int $navigationSort = 1;

    public string $activeTab = 'todos';

    public ?int $selectedDispatchId = null;

    public ?int $selectedDriverId = null;

    /** @var array<string, int> */
    public array $tabsStats = [];

    /** @var array<int, array<string, mixed>> */
    public array $dispatchList = [];

    /** @var array<int, array<string, mixed>> */
    public array $initialPilots = [];

    public ?array $initialSelectedDetails = null;

    /** @var array<int, array<string, mixed>> */
    public array $initialSelectedStops = [];

    public ?int $returnOrderId = null;

    public ?int $returnProductId = null;

    public ?int $returnColorId = null;

    public float $returnQuantity = 0;

    public string $returnReason = '';

    public string $returnNotes = '';

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

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

            $this->loadSnapshot();
            $this->hydrateInitialSelection();

            return;
        }

        $defaultDispatch = $this->mapService()->scopeQuery(auth()->user())
            ->whereNotNull('driver_id')
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->latest('dispatch_date')
            ->first();

        if ($defaultDispatch) {
            $this->selectedDispatchId = $defaultDispatch->id;
            $this->selectedDriverId = $defaultDispatch->driver_id;
        }

        $this->loadSnapshot();
        $this->hydrateInitialSelection();
    }

    protected function hydrateInitialSelection(): void
    {
        if (! $this->selectedDriverId) {
            return;
        }

        $payload = $this->mapService()->selectedDriverPayload(
            auth()->user(),
            $this->selectedDriverId,
            $this->activeTab,
        );

        $this->initialSelectedDetails = $payload['details'];
        $this->initialSelectedStops = $payload['stops'];
    }

    protected function loadSnapshot(): void
    {
        $snapshot = $this->mapService()->snapshot(auth()->user(), $this->activeTab);
        $this->tabsStats = $snapshot['stats'];
        $this->dispatchList = $snapshot['dispatches'];
        $this->initialPilots = $snapshot['pilots'];
    }

    protected function broadcastSnapshot(): void
    {
        $this->loadSnapshot();

        $this->dispatch(
            'dispatch-list-updated',
            stats: $this->tabsStats,
            dispatches: $this->dispatchList,
            pilots: $this->initialPilots,
        );
    }

    public function getTabsStats(): array
    {
        return $this->tabsStats ?: $this->mapService()->tabsStats(auth()->user());
    }

    public function getDispatches(): array
    {
        return $this->dispatchList ?: $this->mapService()->dispatchList(auth()->user(), $this->activeTab);
    }

    public function getActivePilotsLocations(): array
    {
        return $this->mapService()->activePilotsLocations(auth()->user(), $this->activeTab);
    }

    #[Renderless]
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedDispatchId = null;
        $this->selectedDriverId = null;

        $this->mapService()->forgetForUser(auth()->user());
        $snapshot = $this->mapService()->snapshot(auth()->user(), $this->activeTab);
        $this->tabsStats = $snapshot['stats'];
        $this->dispatchList = $snapshot['dispatches'];

        $this->dispatch(
            'dashboard-tab-changed',
            tab: $tab,
            stats: $snapshot['stats'],
            dispatches: $snapshot['dispatches'],
            pilots: $snapshot['pilots'],
        );
    }

    #[Renderless]
    public function selectDriver(int $driverId): void
    {
        $this->selectedDriverId = $driverId;
        $this->selectedDispatchId = $this->mapService()->driverLatestDispatchId(
            auth()->user(),
            $driverId,
            $this->activeTab,
        );

        $payload = $this->mapService()->selectedDriverPayload(
            auth()->user(),
            $driverId,
            $this->activeTab,
        );

        $this->dispatch(
            'dispatch-selected',
            driverId: $driverId,
            details: $payload['details'],
            locations: $payload['locations'],
            stops: $payload['stops'],
        );
    }

    public function getSelectedDriverDetails(): ?array
    {
        if (! $this->selectedDriverId) {
            return null;
        }

        return $this->mapService()->selectedDriverDetails(
            auth()->user(),
            $this->selectedDriverId,
            $this->activeTab,
        );
    }

    public function getSelectedDriverLocations(): array
    {
        if (! $this->selectedDriverId) {
            return [];
        }

        return $this->mapService()->selectedDriverLocations(
            auth()->user(),
            $this->selectedDriverId,
            $this->activeTab,
        );
    }

    public function getSelectedDriverStops(): array
    {
        if (! $this->selectedDriverId) {
            return [];
        }

        return $this->mapService()->selectedDriverStops(
            auth()->user(),
            $this->selectedDriverId,
            $this->activeTab,
        );
    }

    public function completeOrder(int $orderId): void
    {
        try {
            DB::transaction(function () use ($orderId) {
                $order = Order::find($orderId);
                if (! $order) {
                    throw new \Exception('Pedido no encontrado.');
                }

                $order->update(['status' => 'completed']);

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

            $this->invalidateMapCache();
            $this->syncSelectedDriverState();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

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

    public function submitReturn(): void
    {
        try {
            DB::transaction(function () {
                $order = Order::find($this->returnOrderId);
                if (! $order) {
                    throw new \Exception('Pedido no encontrado.');
                }
                $dispatch = $order->dispatch;
                if (! $dispatch) {
                    throw new \Exception('El pedido no tiene un despacho asignado.');
                }

                OrderReturn::create([
                    'dispatch_id' => $dispatch->id,
                    'order_id' => $order->id,
                    'product_id' => $this->returnProductId,
                    'color_id' => $this->returnColorId,
                    'driver_id' => $dispatch->driver_id,
                    'truck_id' => $dispatch->truck_id,
                    'quantity' => $this->returnQuantity,
                    'reason' => $this->returnReason,
                    'status' => 'pending',
                    'notes' => $this->returnNotes,
                    'created_by' => auth()->id(),
                ]);

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
            $this->invalidateMapCache();
            $this->syncSelectedDriverState();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function finishDispatchGlobal(int $dispatchId): void
    {
        try {
            $dispatch = Dispatch::find($dispatchId);
            if (! $dispatch) {
                throw new \Exception('Despacho no encontrado.');
            }

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
            $this->invalidateMapCache();
            $this->broadcastSnapshot();
            $this->dispatch('dispatch-data-changed');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelDispatchGlobal(int $dispatchId): void
    {
        try {
            $dispatch = Dispatch::find($dispatchId);
            if (! $dispatch) {
                throw new \Exception('Despacho no encontrado.');
            }

            app(DispatchService::class)->cancel($dispatch);

            Notification::make()
                ->title('Despacho Cancelado')
                ->body('El stock se ha reincorporado a la bodega origen y se eliminó el registro.')
                ->success()
                ->send();

            $this->selectedDispatchId = null;
            $this->selectedDriverId = null;
            $this->invalidateMapCache();
            $this->broadcastSnapshot();
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

    #[Renderless]
    public function refreshLocations(): array
    {
        $service = $this->mapService();

        return [
            'pilots' => $service->activePilotsLocations(auth()->user(), $this->activeTab),
            'selectedLocations' => $this->selectedDriverId
                ? $service->selectedDriverLocations(auth()->user(), $this->selectedDriverId, $this->activeTab)
                : [],
            'selectedDetails' => $this->selectedDriverId
                ? $service->selectedDriverDetails(auth()->user(), $this->selectedDriverId, $this->activeTab)
                : null,
            'selectedStops' => $this->selectedDriverId
                ? $service->selectedDriverStops(auth()->user(), $this->selectedDriverId, $this->activeTab)
                : [],
        ];
    }

    protected function syncSelectedDriverState(): void
    {
        $this->dispatch('dispatch-data-changed');

        if (! $this->selectedDriverId) {
            $this->dispatch(
                'dashboard-filter-changed',
                pilots: $this->mapService()->activePilotsLocations(auth()->user(), $this->activeTab),
            );

            return;
        }

        $this->selectedDispatchId = $this->mapService()->driverLatestDispatchId(
            auth()->user(),
            $this->selectedDriverId,
            $this->activeTab,
        );

        $payload = $this->mapService()->selectedDriverPayload(
            auth()->user(),
            $this->selectedDriverId,
            $this->activeTab,
        );

        $this->dispatch(
            'dispatch-selected',
            driverId: $this->selectedDriverId,
            details: $payload['details'],
            locations: $payload['locations'],
            stops: $payload['stops'],
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

    protected function invalidateMapCache(): void
    {
        $this->mapService()->forgetForUser(auth()->user());
        $this->loadSnapshot();
    }

    protected function mapService(): DispatchMapDataService
    {
        return app(DispatchMapDataService::class);
    }

    protected function dispatchesQuery(): Builder
    {
        return $this->mapService()->scopeQuery(auth()->user());
    }
}
