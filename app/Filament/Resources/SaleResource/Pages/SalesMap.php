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
            // Buscamos usuarios que tengan al menos una ubicación registrada en el sistema
            $userIdsWithLocation = \App\Models\UserLocation::select('user_id')->distinct()->pluck('user_id');
            $salesUsers = \App\Models\User::whereIn('id', $userIdsWithLocation)->get();
            
            $locations = $salesUsers->map(function ($user) {
                try {
                    $lastLocation = \App\Models\UserLocation::where('user_id', $user->id)
                        ->latest('id') // Usamos id para asegurar el orden cronológico real
                        ->first();
                        
                    if (!$lastLocation || !$lastLocation->lat || !$lastLocation->lng) return null;
                    
                    // La DB guarda timestamps en UTC, pero Carbon los lee como Guatemala (incorrecto)
                    // Necesitamos: 1) decirle a Carbon que es UTC, 2) convertir a Guatemala
                    $createdAt = $lastLocation->created_at;
                    if ($createdAt) {
                        // shiftTimezone('UTC') = "esto en realidad es UTC"
                        // setTimezone('America/Guatemala') = "conviértelo a hora local"
                        $localTime = $createdAt->copy()->shiftTimezone('UTC')->setTimezone('America/Guatemala');
                        $minutesAgo = (int) $localTime->diffInMinutes(now('America/Guatemala'));
                        $isOnline = $minutesAgo <= 5;
                    } else {
                        $localTime = null;
                        $isOnline = false;
                    }

                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'lat' => (float) $lastLocation->lat,
                        'lng' => (float) $lastLocation->lng,
                        'speed' => (float) ($lastLocation->speed ?? 0),
                        'updated_at' => $localTime ? $localTime->diffForHumans() : 'Desconocido',
                        'last_seen_exact' => $localTime ? $localTime->format('d/m/Y h:i:s A') : 'Desconocido',
                        'accuracy' => round((float) ($lastLocation->accuracy ?? 0), 1),
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
