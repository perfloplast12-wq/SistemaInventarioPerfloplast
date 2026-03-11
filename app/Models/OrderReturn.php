<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    protected $fillable = [
        'dispatch_id',
        'order_id',
        'product_id',
        'color_id',
        'driver_id',
        'truck_id',
        'quantity',
        'reason',
        'status',
        'resolved_by',
        'notes',
    ];

    public function dispatch() { return $this->belongsTo(Dispatch::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function color() { return $this->belongsTo(Color::class); }
    public function driver() { return $this->belongsTo(User::class, 'driver_id'); }
    public function truck() { return $this->belongsTo(Truck::class); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by'); }
}
