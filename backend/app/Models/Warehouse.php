<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'notes',
        'is_active',
    ];


    public function locations()
    {
        return $this->hasMany(\App\Models\Location::class);
    }

}
