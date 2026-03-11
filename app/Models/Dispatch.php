<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispatch extends Model
{
    use Auditable;

    protected string $auditModule = 'dispatch';
    protected $fillable = [
        'dispatch_number',
        'truck_id',
        'driver_id',
        'warehouse_id',
        'driver_name',
        'route',
        'dispatch_date',
        'status',
        'total_value',
        'total_products',
        'product_types',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'dispatch_date' => 'datetime',
        'total_value' => 'decimal:2',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispatchItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderReturns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DispatchLocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateTotals(): void
    {
        $this->total_value = $this->items()->sum('subtotal');
        $this->total_products = $this->items()->sum('quantity');
        $this->product_types = $this->items()->count();
        $this->save();
    }

    protected static function booted()
    {
        static::creating(function ($dispatch) {
            if (empty($dispatch->dispatch_number)) {
                $lastDispatch = static::orderBy('id', 'desc')->first();
                $nextId = $lastDispatch ? $lastDispatch->id + 1 : 1;
                $dispatch->dispatch_number = 'D-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
