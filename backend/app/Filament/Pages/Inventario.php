<?php

namespace App\Filament\Pages;

use App\Filament\Resources\FinishedProductResource;
use App\Filament\Resources\InventoryMovementResource;
use App\Filament\Resources\RawMaterialProductResource;
use App\Models\Product;
use Filament\Pages\Page;

class Inventario extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Inventario';
    protected static ?string $title = 'Inventario';
    protected static ?int $navigationSort = 20;

    protected static ?string $navigationGroup = 'Operación';

    protected static string $view = 'filament.pages.inventario';

    public int $totalProducts = 0;
    public int $rawMaterials = 0;
    public int $finishedProducts = 0;

    public function mount(): void
    {
        $this->totalProducts = Product::query()->count();
        $this->rawMaterials = Product::query()->where('type', 'raw_material')->count();
        $this->finishedProducts = Product::query()->where('type', 'finished_product')->count();
    }

    public static function canAccess(): bool
    {
        // Ajustalo a tus permisos si ya están:
        return true;
        // return auth()->user()?->can('products.view') ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getRawMaterialsIndexUrl(): string
    {
        return RawMaterialProductResource::getUrl('index');
    }

    public function getFinishedProductsIndexUrl(): string
    {
        return FinishedProductResource::getUrl('index');
    }



    public function getTotalProductsCount(): int
    {
        return $this->totalProducts ?: Product::query()->count();
    }

    public function getRawMaterialsCount(): int
    {
        return $this->rawMaterials ?: Product::query()->where('type', 'raw_material')->count();
    }

    public function getFinishedProductsCount(): int
    {
        return $this->finishedProducts ?: Product::query()->where('type', 'finished_product')->count();
    }

    // Kardex (historial)
    public function getKardexUrl(): string
    {
        return InventoryMovementResource::getUrl('index');
    }

    // Crear movimiento con tipo ya seleccionado
    public function getMovementCreateUrl(string $type): string
    {
        return InventoryMovementResource::getUrl('create') . '?type=' . urlencode($type);
    }

}