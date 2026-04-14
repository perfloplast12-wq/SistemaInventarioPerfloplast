<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Aplica el movimiento al stock (Facade para el observer).
     */
    public function apply(InventoryMovement $m): void
    {
        DB::transaction(function () use ($m) {
            $qty = (float) $m->quantity;

            // Validación estricta: No permitir movimientos de 0
            if (abs($qty) == 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'La cantidad debe ser mayor a 0.'
                ]);
            }

            // Solo ajustes permiten cantidad negativa (resta). El resto debe ser positivo absoluto.
            if ($m->type !== 'adjust' && $qty < 0) {
                 throw ValidationException::withMessages([
                    'quantity' => 'La cantidad no puede ser negativa para este tipo de movimiento.'
                ]);
            }

            match ($m->type) {
                'in' => $this->handleIn($m, $qty),
                'out' => $this->handleOut($m, $qty),
                'transfer' => $this->handleTransfer($m, $qty),
                'adjust' => $this->handleAdjust($m, $qty),
                'return' => $this->handleReturn($m, $qty),
                default => throw ValidationException::withMessages(['type' => "Tipo de movimiento inválido: {$m->type}"]),
            };
        });
    }

    /**
     * Revierte el movimiento (para eliminar).
     */
    public function revert(InventoryMovement $m): void
    {
        DB::transaction(function () use ($m) {
            $qty = (float) $m->quantity;
            if (abs($qty) == 0) return;

            $from = $this->resolveFrom($m);
            $to = $this->resolveTo($m);

            // Invertir lógica: lo que entró se quita, lo que salió se agrega
            match ($m->type) {
                'in' => $to ? $this->decreaseStock($m->product_id, $to, $qty, $m->color_id) : null,
                'out' => $from ? $this->increaseStock($m->product_id, $from, $qty, $m->color_id) : null,
                'transfer', 'return' => ($from && $to) ? $this->transferStock($m->product_id, $to, $from, $qty, $m->color_id) : null,
                'adjust' => $this->revertAdjust($m, $qty),
                default => null,
            };
        });
    }

    // ── Lógica específica por tipo ─────────────────────

    private function handleIn(InventoryMovement $m, float $qty): void
    {
        $to = $this->resolveTo($m);
        if (!$to) throw ValidationException::withMessages(['to_warehouse_id' => 'Entrada requiere destino.']);
        
        $this->increaseStock($m->product_id, $to, $qty, $m->color_id);
    }

    private function handleOut(InventoryMovement $m, float $qty): void
    {
        $from = $this->resolveFrom($m);
        if (!$from) throw ValidationException::withMessages(['from_warehouse_id' => 'Salida requiere origen.']);
        
        $this->decreaseStock($m->product_id, $from, $qty, $m->color_id);
    }

    private function handleTransfer(InventoryMovement $m, float $qty): void
    {
        $from = $this->resolveFrom($m);
        $to = $this->resolveTo($m);

        if (!$from || !$to) {
            throw ValidationException::withMessages(['from_warehouse_id' => 'Transferencia requiere origen y destino.']);
        }
        
        if ($from === $to) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'Origen y destino no pueden ser el mismo.']);
        }

        $this->transferStock($m->product_id, $from, $to, $qty, $m->color_id);
    }

    private function handleReturn(InventoryMovement $m, float $qty): void
    {
        $from = $this->resolveFrom($m);
        $to = $this->resolveTo($m);

        if (!$from || !$to) {
            throw ValidationException::withMessages(['from_truck_id' => 'Devolución requiere origen (Camión) y destino (Bodega).']);
        }

        $this->transferStock($m->product_id, $from, $to, $qty, $m->color_id);
    }

    private function handleAdjust(InventoryMovement $m, float $qty): void
    {
        $loc = $this->resolveTo($m) ?: $this->resolveFrom($m);
        if (!$loc) throw ValidationException::withMessages(['to_warehouse_id' => 'Ajuste requiere ubicación.']);

        // Si qty es negativo, restamos. Si positivo, sumamos.
        if ($qty >= 0) {
            $this->increaseStock($m->product_id, $loc, $qty, $m->color_id);
        } else {
            $this->decreaseStock($m->product_id, $loc, abs($qty), $m->color_id);
        }
    }

    private function revertAdjust(InventoryMovement $m, float $qty): void
    {
        $loc = $this->resolveTo($m) ?: $this->resolveFrom($m);
        if (!$loc) return;

        // Invertir operación
        if ($qty >= 0) {
            $this->decreaseStock($m->product_id, $loc, $qty, $m->color_id);
        } else {
            $this->increaseStock($m->product_id, $loc, abs($qty), $m->color_id);
        }
    }

    // ── Métodos Core (Estrictos) ───────────────────────

    public function increaseStock(?int $productId, ?array $location, float $qty, ?int $colorId = null): void
    {
        if (!$productId || !$location) return;
        $stock = Stock::query()
            ->where('product_id', $productId)
            ->where('color_id', $colorId)
            ->where('warehouse_id', $location['warehouse_id'])
            ->where('truck_id', $location['truck_id'])
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            $stock = Stock::create([
                'product_id'   => $productId,
                'color_id'     => $colorId,
                'warehouse_id' => $location['warehouse_id'],
                'truck_id'     => $location['truck_id'],
                'quantity'     => 0,
            ]);
        }

        $stock->quantity += $qty;
        $stock->save();
    }

    public function decreaseStock(?int $productId, ?array $location, float $qty, ?int $colorId = null): void
    {
        if (!$productId || !$location) return;
        $stock = Stock::query()
            ->where('product_id', $productId)
            ->where('color_id', $colorId)
            ->where('warehouse_id', $location['warehouse_id'])
            ->where('truck_id', $location['truck_id'])
            ->lockForUpdate()
            ->first();

        $current = $stock ? (float)$stock->quantity : 0.0;

        if ($current < $qty) {
            $label = $location['warehouse_id'] 
                ? "Bodega" 
                : "Camión";
            
            $colorName = $colorId ? (\App\Models\Color::find($colorId)?->name ?? "ID:$colorId") : "N/A";

            throw ValidationException::withMessages([
                'quantity' => "Stock insuficiente en {$label} para color '{$colorName}'. Disponible: {$current}, Requerido: {$qty}"
            ]);
        }

        $stock->quantity -= $qty;
        $stock->save();
    }

    public function transferStock(int $productId, array $from, array $to, float $qty, ?int $colorId = null): void
    {
        // Primero retirar (valida stock)
        $this->decreaseStock($productId, $from, $qty, $colorId);
        // Luego agregar
        $this->increaseStock($productId, $to, $qty, $colorId);
    }

    // ── Helpers ────────────────────────────────────────

    private function resolveFrom(InventoryMovement $m): ?array
    {
        if ($m->from_warehouse_id) return ['warehouse_id' => $m->from_warehouse_id, 'truck_id' => null];
        if ($m->from_truck_id)     return ['warehouse_id' => null, 'truck_id' => $m->from_truck_id];
        return null;
    }

    private function resolveTo(InventoryMovement $m): ?array
    {
        if ($m->to_warehouse_id) return ['warehouse_id' => $m->to_warehouse_id, 'truck_id' => null];
        if ($m->to_truck_id)     return ['warehouse_id' => null, 'truck_id' => $m->to_truck_id];
        return null;
    }
}
