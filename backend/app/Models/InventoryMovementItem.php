<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovementItem extends Model
{
    protected $fillable = [
        'inventory_movement_id',
        'product_id',
        'quantity',
        'unit_cost',
    ];

    public function movement()
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
