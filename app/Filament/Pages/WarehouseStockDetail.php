<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use App\Models\Warehouse;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarehouseStockDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static bool $shouldRegisterNavigation = false; // Hidden from sidebar
    protected static string $view = 'filament.pages.warehouse-stock-detail';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('volver')
                ->label('Volver a Inventario')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.admin.pages.inventario'))
                ->color('gray'),
        ];
    }

    public ?int $warehouseId = null;
    public ?string $productType = null;
    public ?string $warehouseName = null;
    public ?string $typeLabel = null;

    public function mount(): void
    {
        $this->warehouseId = (int) request()->query('warehouse');
        $this->productType = request()->query('type', 'raw_material');

        $warehouse = Warehouse::find($this->warehouseId);
        $this->warehouseName = $warehouse?->name ?? 'Bodega';
        $this->typeLabel = $this->productType === 'raw_material' ? 'Materia Prima' : 'Producto Terminado';
    }

    public function getTitle(): string
    {
        return "{$this->warehouseName} — {$this->typeLabel}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            Inventario::getUrl() => 'Inventario',
            '#' => "{$this->warehouseName} — {$this->typeLabel}",
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::query()
                    ->where('warehouse_id', $this->warehouseId)
                    ->where('quantity', '!=', 0)
                    ->whereHas('product', fn (Builder $q) => $q->where('type', $this->productType))
                    ->with(['product', 'color'])
            )
            ->groups([
                Tables\Grouping\Group::make('product.name')
                    ->label('Producto')
                    ->getTitleFromRecordUsing(fn ($record) => "{$record->product->name} — Existencia Total: " . number_format($record->product->stocks->where('warehouse_id', $this->warehouseId)->sum('quantity'), 0, '.', ','))
                    ->collapsible(),
            ])
            ->defaultGroup('product.name')
            ->columns([
                Tables\Columns\TextColumn::make('color.display_name')
                    ->label('Color / Variante')
                    ->placeholder('Base / Único')
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->formatStateUsing(fn ($state) => number_format($state, (round($state) == $state ? 0 : 2), '.', ','))
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->alignment('right'),
            ])
            ->paginated([25, 50, 100])
            ->striped();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('inventory.view') ?? false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }
}
