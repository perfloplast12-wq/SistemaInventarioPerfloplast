<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use Auditable;

    protected string $auditModule = 'sales';
    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = self::generateUniqueSaleNumber();
            }
            if (empty($sale->created_by)) {
                $sale->created_by = auth()->id();
            }
        });
    }

    public static function generateUniqueSaleNumber(): string
    {
        $latest = self::latest('id')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        return 'V-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
    protected $fillable = [
        'sale_number',
        'sale_date',
        'status',
        'from_warehouse_id',
        'from_truck_id',
        'origin_type',
        'customer_name',
        'customer_nit',
        'note',
        'total',
        'discount_type',
        'discount_value',
        'discount_amount',
        'receipt_number',
        'receipt_path',
        'created_by',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function fromTruck(): BelongsTo
    {
        return $this->belongsTo(Truck::class, 'from_truck_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->total - $this->total_paid;
    }
}
