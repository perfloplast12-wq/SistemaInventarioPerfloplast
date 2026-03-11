<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchLocation extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'dispatch_id',
        'lat',
        'lng',
        'speed',
        'heading',
        'created_at'
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'speed' => 'float',
        'heading' => 'float',
        'created_at' => 'datetime',
    ];

    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class);
    }
}
