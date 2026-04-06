<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use Auditable;

    protected string $auditModule = 'purchases';

    protected $fillable = [
        'purchase_number',
        'invoice_series',
        'supplier_invoice_number',
        'supplier_id',
        'purchase_date',
        'payment_condition',
        'due_date',
        'total',
        'tax_amount',
        'status',
        'category',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'due_date' => 'date',
        'total' => 'decimal:3',
        'tax_amount' => 'decimal:3',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::creating(function ($purchase) {
            if (empty($purchase->purchase_number)) {
                $latest = self::latest('id')->first();
                $nextId = $latest ? $latest->id + 1 : 1;
                $purchase->purchase_number = 'COM-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
