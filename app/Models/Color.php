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
        'hex_code',
        'brightness',
        'contrast',
        'image_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Accessor for display_name
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * More descriptive label for grouped selects
     */
    public function getDescriptiveLabelAttribute(): string
    {
        $label = $this->name;
        if ($this->code) {
            $label .= " — {$this->code}";
        }
        return $label;
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['hex_code', 'image_url', 'brightness', 'contrast'])
            ->withTimestamps();
    }
}
