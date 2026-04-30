<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;


class Warehouse extends Model
{
    use Auditable;

    protected string $auditModule = 'catalogs';
    protected $fillable = [
        'name',
        'code',
        'type',
        'notes',
        'is_factory',
        'is_active',
    ];

    protected $casts = [
        'is_factory' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($warehouse) {
            if ($warehouse->is_factory) {
                // Si esta bodega se marca como fábrica, quitamos el flag a todas las demás
                static::where('id', '!=', $warehouse->id)
                    ->where('is_factory', true)
                    ->update(['is_factory' => false]);
            }
        });
    }

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }


    public function locations()
    {
        return $this->hasMany(\App\Models\Location::class);
    }

    public function stocks()
    {
        return $this->hasMany(\App\Models\Stock::class);
    }
}
