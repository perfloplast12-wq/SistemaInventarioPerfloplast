<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use Auditable;

    protected string $auditModule = 'orders';
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_nit',
        'delivery_address',
        'phone',
        'order_date',
        'payment_method',
        'payment_status',
        'notes',
        'status',
        'dispatch_id',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $lastOrder = static::orderBy('id', 'desc')->first();
                $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
                $order->order_number = 'ORD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items->sum('subtotal');
    }
}
