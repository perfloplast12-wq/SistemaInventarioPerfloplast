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
                    
                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'lat' => (float) $lastLocation->lat,
                        'lng' => (float) $lastLocation->lng,
                        'speed' => (float) ($lastLocation->speed ?? 0),
                        'updated_at' => $lastLocation->created_at ? $lastLocation->created_at->setTimezone('America/Guatemala')->diffForHumans() : 'Desconocido',
                        'last_seen_exact' => $lastLocation->created_at ? $lastLocation->created_at->setTimezone('America/Guatemala')->format('d/m/Y h:i:s A') : 'Desconocido',
                        'accuracy' => round((float) ($lastLocation->accuracy ?? 0), 1),
                        'is_online' => $lastLocation->created_at ? $lastLocation->created_at->gt(now()->subMinutes(15)) : false,
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
