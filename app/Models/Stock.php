<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'color_id',
        'warehouse_id',
        'truck_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    // ── Helpers ─────────────────────────────────────

    public function isWarehouse(): bool
    {
        return $this->warehouse_id !== null;
    }

    public function isTruck(): bool
    {
        return $this->truck_id !== null;
    }

    /**
     * Etiqueta humana de la ubicación.
     */
    public function getLocationLabelAttribute(): string
    {
        if ($this->isWarehouse()) {
            return 'Bodega: ' . ($this->warehouse?->name ?? 'N/A');
        }

        if ($this->isTruck()) {
            $truck = $this->truck;
            return 'Camión: ' . ($truck?->name ?? $truck?->plate ?? 'N/A');
        }

        return 'Sin ubicación';
    }
}
