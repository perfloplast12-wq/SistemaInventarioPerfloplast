<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    use Auditable;

    protected string $auditModule = 'production';
    use HasFactory;

    protected $fillable = [
        'production_number',
        'production_date',
        'product_id',
        'color_id',
        'shift_id',
        'to_warehouse_id',
        'quantity',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'production_date' => 'datetime',
        'quantity' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionItem::class);
    }

    protected static function booted()
    {
        static::creating(function ($production) {
            if (empty($production->production_number)) {
                $production->production_number = self::generateProductionNumber();
            }
        });
    }

    public function confirm(): void
    {
        if ($this->status !== 'draft') {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            // 1. Salida de Materias Primas
            foreach ($this->items as $item) {
                \App\Models\InventoryMovement::create([
                    'type' => 'out',
                    'product_id' => $item->product_id,
                    'color_id' => null, 
                    'quantity' => $item->quantity,
                    'unit_cost' => 0,
                    'from_warehouse_id' => $this->to_warehouse_id,
                    'note' => "Consumo por Producción #{$this->production_number}",
                    'created_by' => auth()->id(),
                    'source_type' => 'production',
                    'source_id' => $this->id,
                ]);
            }

            // 2. Entrada de Producto Terminado
            \App\Models\InventoryMovement::create([
                'type' => 'in',
                'product_id' => $this->product_id,
                'color_id' => $this->color_id, 
                'quantity' => $this->quantity,
                'unit_cost' => $this->product->sale_price ?? 0,
                'to_warehouse_id' => $this->to_warehouse_id,
                'note' => "Producción finalizada #{$this->production_number}",
                'created_by' => auth()->id(),
                'source_type' => 'production',
                'source_id' => $this->id,
            ]);

            $this->update(['status' => 'confirmed']);
        });
    }

    public static function generateProductionNumber(): string
    {
        $latest = self::latest('id')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        return 'P-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
