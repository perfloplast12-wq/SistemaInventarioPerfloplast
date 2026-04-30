<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use App\Filament\Resources\WarehouseResource;
use App\Filament\Resources\TruckResource;
use App\Filament\Resources\ColorResource;

// ✅ SI YA EXISTEN en tu proyecto, descomenta y ajusta el nombre exacto:
use App\Filament\Resources\UnitOfMeasureResource;
use App\Filament\Resources\ShiftResource;

class Catalogos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Ajustes de Operación';
    protected static ?string $title = 'Ajustes de Operación';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'AJUSTES DE OPERACIÓN';

    protected static string $view = 'filament.pages.catalogos';

    // ✅ Quita el heading duplicado de Filament (deja solo el del blade)
    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public static function canAccess(): bool
    {
        if (auth()->user()?->hasAnyRole(['production', 'sales'])) {
            return false;
        }
        return auth()->user()?->can('catalogs.view') ?? false;
    }

    // -------------------- BODEGAS --------------------
    public function getWarehousesUrl(): string
    {
        return WarehouseResource::getUrl('index');
    }

    public function getWarehousesCreateUrl(): string
    {
        return WarehouseResource::getUrl('create');
    }

    // -------------------- UNIDADES DE MEDIDA --------------------
    public function getUnitsUrl(): string
    {
        return UnitOfMeasureResource::getUrl('index');
    }

    public function getUnitsCreateUrl(): string
    {
        return UnitOfMeasureResource::getUrl('create');
    }

    // -------------------- COLORES --------------------
    public function getColorsUrl(): string
    {
        return ColorResource::getUrl('index');
    }

    public function getColorsCreateUrl(): string
    {
        // ColorResource es un recurso simple (ManageRecords), no tiene ruta 'create' propia.
        // Redirigimos al index donde se abre el modal.
        return ColorResource::getUrl('index');
    }
}
