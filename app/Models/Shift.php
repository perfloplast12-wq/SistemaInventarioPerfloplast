<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'daily_goal',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'daily_goal' => 'decimal:2',
    ];
}
