<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'type',
        'unit_of_measure_id',
        'is_active',
        'description',
        'color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
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
