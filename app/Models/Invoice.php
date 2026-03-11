<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'sale_id',
        'order_id',
        'customer_name',
        'customer_nit',
        'invoice_date',
        'payment_method',
        'sale_type',
        'subtotal',
        'discount_amount',
        'total',
        'amount_paid',
        'change_amount',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $lastInvoice = static::orderBy('id', 'desc')->first();
                $nextId = $lastInvoice ? $lastInvoice->id + 1 : 1;
                $invoice->invoice_number = 'F-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
