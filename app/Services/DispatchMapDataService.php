<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\DispatchLocation;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DispatchMapDataService
{
    private const TABS = ['todos', 'in_progress', 'completed', 'pending', 'delivered'];

    private const SNAPSHOT_TTL = 20;

    private const ROUTE_POINT_LIMIT = 250;

    public function scopeQuery(?User $user): Builder
    {
        $query = Dispatch::query();

        if (! $user) {
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

    public function snapshot(?User $user, string $activeTab): array
    {
        $userId = $user?->id ?? 0;

        return Cache::remember(
            $this->snapshotKey($userId, $activeTab),
            self::SNAPSHOT_TTL,
            fn () => [
                'stats' => $this->tabsStats($user),
                'dispatches' => $this->dispatchList($user, $activeTab),
                'pilots' => $this->activePilotsLocations($user, $activeTab),
            ],
        );
    }

    public function forgetForUser(?User $user): void
    {
        if (! $user) {
            return;
        }

        foreach (self::TABS as $tab) {
            Cache::forget($this->snapshotKey($user->id, $tab));
        }
    }

    public function tabsStats(?User $user): array
    {
        $row = $this->scopeQuery($user)
            ->selectRaw('COUNT(*) as todos')
            ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered")
            ->first();

        return [
            'todos' => (int) ($row->todos ?? 0),
            'in_progress' => (int) ($row->in_progress ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'delivered' => (int) ($row->delivered ?? 0),
        ];
    }

    public function dispatchList(?User $user, string $activeTab): array
    {
        $query = $this->scopeQuery($user)
            ->with(['driver', 'truck', 'orders']);

        if ($activeTab !== 'todos') {
            $query->where('status', $activeTab);
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
                    'driver_id' => $first->driver_id,
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

    public function activePilotsLocations(?User $user, string $activeTab): array
    {
        $query = $this->scopeQuery($user)
            ->with(['driver', 'truck', 'orders']);

        if ($activeTab !== 'todos') {
            $query->where('status', $activeTab);
        }

        $dispatches = $query
            ->latest('dispatch_date')
            ->limit(100)
            ->get();

        if ($dispatches->isEmpty()) {
            return [];
        }

        $dispatchIds = $dispatches->pluck('id')->all();
        $driverIds = $dispatches->pluck('driver_id')->filter()->unique()->values()->all();

        $latestDispatchLocations = $this->latestDispatchLocationsByDispatchId($dispatchIds);
        $latestUserLocations = $this->latestUserLocationsByUserId($driverIds);

        $rows = [];

        foreach ($dispatches as $d) {
            $lastLoc = $latestDispatchLocations->get($d->id);

            if (! $lastLoc && $d->driver_id) {
                $lastLoc = $latestUserLocations->get($d->driver_id);
            }

            if (! $lastLoc || ! $lastLoc->lat || ! $lastLoc->lng) {
                continue;
            }

            $lastSeen = $lastLoc->created_at;

            $rows[] = [
                'driver_id' => $d->driver_id,
                'driver_key' => $d->driver_id ?? strtolower(trim($d->driver_name ?? '')),
                'dispatch_id' => $d->id,
                'driver_name' => $d->driver?->name ?? $d->driver_name ?? 'Sin Piloto',
                'truck_name' => $d->truck?->name ?? 'Sin Camión',
                'status' => $d->status,
                'route' => $d->route,
                'lat' => (float) $lastLoc->lat,
                'lng' => (float) $lastLoc->lng,
                'timestamp' => $lastSeen?->timestamp ?? 0,
                'updated_at' => $lastSeen?->diffForHumans() ?? 'Sin registro',
                'last_seen_exact' => $lastSeen?->format('d/m/Y h:i:s A') ?? 'Sin registro',
                'is_online' => $lastSeen ? $lastSeen->diffInSeconds(now()) <= 120 : false,
                'speed' => $lastLoc->speed ?? 0,
                'heading' => $lastLoc->heading ?? 0,
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

    public function selectedDriverPayload(?User $user, int $driverId, string $activeTab): array
    {
        return [
            'details' => $this->selectedDriverDetails($user, $driverId, $activeTab),
            'locations' => $this->selectedDriverLocations($user, $driverId, $activeTab),
            'stops' => $this->selectedDriverStops($user, $driverId, $activeTab),
        ];
    }

    public function selectedDriverDetails(?User $user, int $driverId, string $activeTab): ?array
    {
        $dispatches = $this->driverDispatchesQuery($user, $driverId, $activeTab)
            ->with(['driver', 'truck', 'orders'])
            ->get();

        if ($dispatches->isEmpty()) {
            return null;
        }

        $first = $dispatches->first();
        $driverName = $first->driver?->name ?? $first->driver_name ?? 'Sin Piloto';
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

        $allOrders = $dispatches->flatMap(fn ($d) => $d->orders);
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
            'driver_id' => $driverId,
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
            'location' => $this->driverLatestLocation($driverId, $dispatchIds),
        ];
    }

    public function selectedDriverLocations(?User $user, int $driverId, string $activeTab): array
    {
        $dispatches = $this->driverDispatchesQuery($user, $driverId, $activeTab)->get(['id']);

        if ($dispatches->isEmpty()) {
            return $this->userLocationPoint($driverId);
        }

        $dispatchIds = $dispatches->pluck('id')->all();
        $latestByDispatch = $this->latestDispatchLocationsByDispatchId($dispatchIds);

        $bestDispatchId = null;
        $bestLocationTime = null;

        foreach ($dispatchIds as $dispatchId) {
            $lastLoc = $latestByDispatch->get($dispatchId);
            if (! $lastLoc) {
                continue;
            }

            if (! $bestLocationTime || $lastLoc->created_at > $bestLocationTime) {
                $bestLocationTime = $lastLoc->created_at;
                $bestDispatchId = $dispatchId;
            }
        }

        $userLocation = $this->latestUserLocationsByUserId([$driverId])->get($driverId);

        if (! $bestDispatchId) {
            return $this->formatUserLocationPoint($userLocation);
        }

        $locations = DispatchLocation::query()
            ->where('dispatch_id', $bestDispatchId)
            ->where(fn (Builder $query) => $query->whereNull('speed')->orWhere('speed', '!=', -1))
            ->orderByDesc('created_at')
            ->limit(self::ROUTE_POINT_LIMIT)
            ->get()
            ->sortBy('created_at')
            ->map(fn ($loc) => [
                'lat' => (float) $loc->lat,
                'lng' => (float) $loc->lng,
                'created_at' => $loc->created_at->format('H:i:s'),
            ])
            ->values()
            ->toArray();

        if ($userLocation && (! $bestLocationTime || $userLocation->created_at?->gt($bestLocationTime))) {
            $locations[] = [
                'lat' => (float) $userLocation->lat,
                'lng' => (float) $userLocation->lng,
                'created_at' => $userLocation->created_at?->format('H:i:s'),
            ];
        }

        return $locations;
    }

    public function selectedDriverStops(?User $user, int $driverId, string $activeTab): array
    {
        $dispatchIds = $this->driverDispatchesQuery($user, $driverId, $activeTab)->pluck('id')->toArray();

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
                    'items' => $order->items->map(fn ($item) => [
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

    public function driverLatestDispatchId(?User $user, int $driverId, string $activeTab): ?int
    {
        return $this->driverDispatchesQuery($user, $driverId, $activeTab)
            ->latest('dispatch_date')
            ->value('id');
    }

    private function driverDispatchesQuery(?User $user, int $driverId, string $activeTab): Builder
    {
        $query = $this->scopeQuery($user)->where('driver_id', $driverId);

        if ($activeTab !== 'todos') {
            $query->where('status', $activeTab);
        }

        return $query;
    }

    private function latestDispatchLocationsByDispatchId(array $dispatchIds): Collection
    {
        if ($dispatchIds === []) {
            return collect();
        }

        $latestIds = DispatchLocation::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('dispatch_id', $dispatchIds)
            ->groupBy('dispatch_id')
            ->pluck('id');

        return DispatchLocation::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('dispatch_id');
    }

    private function latestUserLocationsByUserId(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        $latestIds = UserLocation::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('user_id', $userIds)
            ->where(fn (Builder $query) => $query->whereNull('accuracy')->orWhere('accuracy', '!=', -1))
            ->groupBy('user_id')
            ->pluck('id');

        return UserLocation::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('user_id');
    }

    private function driverLatestLocation(int $driverId, array $dispatchIds = []): ?array
    {
        $dispatchLocation = null;

        if ($dispatchIds !== []) {
            $dispatchLocation = DispatchLocation::query()
                ->whereIn('dispatch_id', $dispatchIds)
                ->where(fn (Builder $query) => $query->whereNull('speed')->orWhere('speed', '!=', -1))
                ->orderByDesc('created_at')
                ->first();
        }

        $userLocation = UserLocation::query()
            ->where('user_id', $driverId)
            ->where(fn (Builder $query) => $query->whereNull('accuracy')->orWhere('accuracy', '!=', -1))
            ->orderByDesc('created_at')
            ->first();

        $location = $dispatchLocation;
        if ($userLocation && (! $dispatchLocation || $userLocation->created_at?->gt($dispatchLocation->created_at))) {
            $location = $userLocation;
        }

        if (! $location || ! $location->lat || ! $location->lng) {
            return null;
        }

        $lastSeen = $location->created_at;

        return [
            'lat' => (float) $location->lat,
            'lng' => (float) $location->lng,
            'speed' => round((float) ($location->speed ?? 0), 1),
            'heading' => round((float) ($location->heading ?? 0), 1),
            'accuracy' => isset($location->accuracy) ? round((float) $location->accuracy, 1) : null,
            'updated_at' => $lastSeen?->diffForHumans() ?? 'Sin registro',
            'last_seen_exact' => $lastSeen?->format('d/m/Y h:i:s A') ?? 'Sin registro',
            'is_online' => $lastSeen ? $lastSeen->diffInSeconds(now()) <= 120 : false,
        ];
    }

    private function userLocationPoint(int $driverId): array
    {
        return $this->formatUserLocationPoint(
            $this->latestUserLocationsByUserId([$driverId])->get($driverId),
        );
    }

    private function formatUserLocationPoint(?UserLocation $userLocation): array
    {
        if (! $userLocation) {
            return [];
        }

        return [[
            'lat' => (float) $userLocation->lat,
            'lng' => (float) $userLocation->lng,
            'created_at' => $userLocation->created_at?->format('H:i:s'),
        ]];
    }

    private function snapshotKey(int $userId, string $activeTab): string
    {
        return 'dispatch_map_snapshot_'.$userId.'_'.$activeTab;
    }
}
