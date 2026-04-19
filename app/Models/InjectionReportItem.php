<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InjectionReportItem extends Model
{
    protected $fillable = [
        'injection_report_id',
        'date',
        'day',
        'activity',
        'description',
        'result',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function report()
    {
        return $this->belongsTo(InjectionReport::class, 'injection_report_id');
    }
}
