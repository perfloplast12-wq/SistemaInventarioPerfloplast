<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    use Auditable;

    protected string $auditModule = 'catalogs';
    use HasFactory;

    protected $fillable = [
        'name',
        'variant',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Accessor for display_name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->variant) {
            return "{$this->name} - {$this->variant} ({$this->code})";
        }

        return "{$this->name} ({$this->code})";
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }
}
