<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;


class Truck extends Model
{
    use Auditable;

    protected string $auditModule = 'catalogs';
    protected $fillable = [
        'name',
        'plate',
        'driver_id',
        'brand',
        'model',
        'driver_name',
        'capacity_kg',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }



    public function locations()
    {
        return $this->hasMany(\App\Models\Location::class);
    }

    public function driver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public static function booted()
    {
        static::saving(function ($truck) {
            if (blank($truck->name)) {
                $truck->name = 'Camion ' . $truck->plate;
            }
        });
    }

}
