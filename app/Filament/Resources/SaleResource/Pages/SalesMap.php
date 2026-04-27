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
        $salesUsers = \App\Models\User::role(['sales', 'vendedor'])->get();
        
        $locations = $salesUsers->map(function ($user) {
            $lastLocation = \App\Models\UserLocation::where('user_id', $user->id)
                ->latest()
                ->first();
                
            if (!$lastLocation) return null;
            
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'lat' => $lastLocation->lat,
                'lng' => $lastLocation->lng,
                'speed' => $lastLocation->speed,
                'updated_at' => $lastLocation->created_at->diffForHumans(),
                'is_online' => $lastLocation->created_at->gt(now()->subMinutes(10)),
            ];
        })->filter()->values();

        return [
            'locations' => $locations,
        ];
    }
}
