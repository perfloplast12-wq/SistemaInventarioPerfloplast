<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'is_active',
        'notes',
        'warehouse_id',
        'truck_id',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }
}
