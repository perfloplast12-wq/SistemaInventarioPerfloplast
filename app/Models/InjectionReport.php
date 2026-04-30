<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class InjectionReport extends Model
{
    protected $fillable = [
        'user_id',
        'employee_name',
        'position',
        'department',
        'week_range',
        'proposals',
        'next_week_plan',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InjectionReportItem::class);
    }
}
