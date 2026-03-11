<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use Auditable;

    protected string $auditModule = 'products';
    protected $fillable = [
        'name',
        'sku',
        'type',
        'unit_of_measure_id',
        'presentation_unit_id',
        'units_per_presentation',
        'is_active',
        'description',
        'color',
        'color_id',
        'sale_price',
        'cost_price',
        'purchase_cost',
    ];

    protected $casts = [
        'units_per_presentation' => 'decimal:4',
        'is_active' => 'boolean',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'purchase_cost' => 'decimal:2',
    ];

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function stocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function presentationUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'presentation_unit_id');
    }

    public function recipes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'finished_product_id');
    }

    /**
     * Convierte una cantidad en unidades de presentación a la unidad base (KG).
     * Ejemplo: 5 Sacos * 55 unidades/saco = 275 KG
     */
    public function convertToUnitBase(float $presentationQuantity): float
    {
        return $presentationQuantity * (float)($this->units_per_presentation ?: 1);
    }

    /**
     * Convierte una cantidad de la unidad base (KG) a unidades de presentación.
     * Ejemplo: 275 KG / 55 unidades/saco = 5 Sacos
     */
    public function convertToPresentationUnit(float $baseQuantity): float
    {
        $factor = (float)($this->units_per_presentation ?: 1);
        return $factor > 0 ? $baseQuantity / $factor : $baseQuantity;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'raw_material' => 'Materia prima',
            'finished_product' => 'Producto terminado',
            default => $this->type,
        };
    }
}
