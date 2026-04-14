<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'type',
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'from_truck_id',
        'to_truck_id',
        'quantity',
        'unit_cost',
        'note',
        'created_by',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function fromTruck(): BelongsTo
    {
        return $this->belongsTo(Truck::class, 'from_truck_id');
    }

    public function toTruck(): BelongsTo
    {
        return $this->belongsTo(Truck::class, 'to_truck_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
