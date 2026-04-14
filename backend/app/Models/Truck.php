<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $fillable = [
        'plate',
        'brand',
        'model',
        'driver_name',
        'is_active',
    ];



    public function locations()
    {
        return $this->hasMany(\App\Models\Location::class);
    }

}
