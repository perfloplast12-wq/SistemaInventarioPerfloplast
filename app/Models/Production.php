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
        'shift_id',
        'to_warehouse_id',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'production_date' => 'datetime',
    ];

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

    // Helper relationships for Filament Repeaters
    public function consumables(): HasMany
    {
        return $this->hasMany(ProductionItem::class)->where('type', 'consumable');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionItem::class)->where('type', 'output');
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

        if (\App\Models\InventoryMovement::where('source_type', 'production')->where('source_id', $this->id)->exists()) {
            $this->update(['status' => 'confirmed']);
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            foreach ($this->items as $item) {
                if ($item->type === 'consumable') {
                    // 1. Salida de Materias Primas
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
                } else {
                    // 2. Entrada de Productos Terminados
                    \App\Models\InventoryMovement::create([
                        'type' => 'in',
                        'product_id' => $item->product_id,
                        'color_id' => $item->color_id, 
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->product->sale_price ?? 0,
                        'to_warehouse_id' => $this->to_warehouse_id,
                        'note' => "Producción finalizada #{$this->production_number}",
                        'created_by' => auth()->id(),
                        'source_type' => 'production',
                        'source_id' => $this->id,
                    ]);
                }
            }

            $this->update(['status' => 'confirmed']);
        });
    }

    public function cancel(): void
    {
        if ($this->status !== 'confirmed') {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            \App\Models\InventoryMovement::where('source_type', 'production')
                ->where('source_id', $this->id)
                ->get()
                ->each(fn ($m) => $m->delete());

            $this->update(['status' => 'cancelled']);
        });
    }

    public static function generateProductionNumber(): string
    {
        $latest = self::latest('id')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        return 'P-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
