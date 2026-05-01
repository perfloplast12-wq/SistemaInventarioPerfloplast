<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\Page;

class SalesMap extends Page
{
    protected static string $resource = SaleResource::class;
    protected static ?string $navigationLabel = 'Mapa de Vendedores';
    protected static ?string $title = 'Mapa de Seguimiento (Vendedores)';
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Área Comercial';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.resources.sale-resource.pages.sales-map';

    public function getViewData(): array
    {
        try {
            // Solo mostramos usuarios activos que tengan el rol 'sales' y tengan ubicación registrada
            $userIdsWithLocation = \App\Models\UserLocation::select('user_id')->distinct()->pluck('user_id');
            $salesUsers = \App\Models\User::role('sales')
                ->where('is_active', true)
                ->whereIn('id', $userIdsWithLocation)
                ->get();
            
            $locations = $salesUsers->map(function ($user) {
                try {
                    $lastLocation = \App\Models\UserLocation::where('user_id', $user->id)
                        ->latest('id') // Usamos id para asegurar el orden cronológico real
                        ->first();
                        
                    if (!$lastLocation || !$lastLocation->lat || !$lastLocation->lng) return null;
                    
                    // accuracy = -1 es señal de desconexión inmediata del tracker
                    $isOfflineSignal = ($lastLocation->accuracy == -1);
                    
                    // Si la última entrada es señal offline, buscar la última posición REAL para mostrar
                    if ($isOfflineSignal) {
                        $realLocation = \App\Models\UserLocation::where('user_id', $user->id)
                            ->where('accuracy', '!=', -1)
                            ->latest('id')
                            ->first();
                        $displayLocation = $realLocation ?? $lastLocation;
                    } else {
                        $displayLocation = $lastLocation;
                    }
                    
                    $createdAt = $displayLocation->created_at;
                    if ($createdAt) {
                        // El sistema usa America/Guatemala por defecto, así que comparamos directamente
                        $minutesAgo = (int) $createdAt->diffInMinutes(now());
                        $localTime = $createdAt;
                        // Offline inmediato si accuracy=-1, o si no hay señal en 5 min (ampliamos un poco el margen)
                        $isOnline = !$isOfflineSignal && $minutesAgo <= 5;
                    } else {
                        $localTime = null;
                        $isOnline = false;
                    }

                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'lat' => (float) $displayLocation->lat,
                        'lng' => (float) $displayLocation->lng,
                        'speed' => (float) ($displayLocation->speed ?? 0),
                        'updated_at' => $localTime ? $localTime->diffForHumans() : 'Desconocido',
                        'last_seen_exact' => $localTime ? $localTime->format('d/m/Y h:i:s A') : 'Desconocido',
                        'accuracy' => $isOfflineSignal ? 0 : round((float) ($displayLocation->accuracy ?? 0), 1),
                        'is_online' => $isOnline,
                    ];
                } catch (\Exception $e) {
                    return null;
                }
            })->filter()->values();

            return [
                'locations' => $locations,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error en SalesMap: " . $e->getMessage());
            return ['locations' => []];
        }
    }
}
