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
            // Buscamos usuarios con roles de ventas de forma segura
            $salesUsers = \App\Models\User::whereHas('roles', function($q) {
                $q->whereIn('name', ['sales', 'vendedor']);
            })->get();
            
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
                        'updated_at' => $lastLocation->created_at ? $lastLocation->created_at->diffForHumans() : 'Desconocido',
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
