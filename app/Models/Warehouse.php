<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use Auditable, SoftDeletes;

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

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }


    public function locations()
    {
        return $this->hasMany(\App\Models\Location::class);
    }

}
