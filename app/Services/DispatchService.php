<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\InventoryMovement;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispatchService
{
    /**
     * Inicia el despacho: transfiere stock de bodega al camión.
     */
    public function start(Dispatch $dispatch): void
    {
        if ($dispatch->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Solo se pueden iniciar despachos en estado pendiente.']);
        }

        if ($dispatch->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'El despacho debe tener al menos un producto.']);
        }

        DB::transaction(function () use ($dispatch) {
            // Verificar si YA existen transferencias vinculadas a este despacho (para evitar duplicados si el usuario cargó manualmente)
            $existingMovements = InventoryMovement::where('source_type', 'dispatch')
                ->where('source_id', $dispatch->id)
                ->where('type', 'transfer')
                ->exists();

            if (!$existingMovements) {
                foreach ($dispatch->items as $item) {
                    // Validar stock en bodega
                    $stock = Stock::where('product_id', $item->product_id)
                        ->where('color_id', $item->color_id)
                        ->where('warehouse_id', $dispatch->warehouse_id)
                        ->first();

                    if (!$stock || $stock->quantity < $item->quantity) {
                        $colorName = $item->color?->name ?: 'Sin Color';
                        throw ValidationException::withMessages([
                            'stock' => "Stock insuficiente en bodega para '{$item->product->name}' ($colorName). Disponible: " . ($stock ? $stock->quantity : 0)
                        ]);
                    }

                    // Crear movimiento de transferencia (OUT de bodega, IN a camión)
                    InventoryMovement::create([
                        'type' => 'transfer',
                        'product_id' => $item->product_id,
                        'color_id' => $item->color_id,
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->product->cost_price ?? 0,
                        'from_warehouse_id' => $dispatch->warehouse_id,
                        'to_truck_id' => $dispatch->truck_id,
                        'note' => "Carga de Despacho #{$dispatch->dispatch_number}",
                        'created_by' => auth()->id(),
                        'source_type' => 'dispatch',
                        'source_id' => $dispatch->id,
                    ]);
                }
            }

            $dispatch->update(['status' => 'in_progress']);
            $dispatch->recalculateTotals();

            // Actualizar estado de los pedidos asociados a 'assigned' (asignado) ya que 'confirmed' no existe en el ENUM
            $dispatch->orders()->update(['status' => 'assigned']);
        });
    }

    /**
     * Marca el despacho como completado (llegó al destino/ruta terminada).
     */
    public function complete(Dispatch $dispatch): void
    {
        if ($dispatch->status !== 'in_progress') {
            throw ValidationException::withMessages(['status' => 'Solo se pueden completar despachos que están en progreso.']);
        }

        $dispatch->update(['status' => 'completed']);
        $dispatch->recalculateTotals();
    }

    /**
     * Marca el despacho como entregado.
     */
    public function deliver(Dispatch $dispatch): void
    {
        if ($dispatch->status !== 'completed') {
            throw ValidationException::withMessages(['status' => 'Solo se pueden marcar como entregados despachos completados.']);
        }

        DB::transaction(function () use ($dispatch) {
            $dispatch->update(['status' => 'delivered']);
            $dispatch->recalculateTotals();

            // 🚀 REBAJAR STOCK DEL CAMIÓ Al ENTREGAR (Resumido por ítem)
            foreach ($dispatch->items as $item) {
                InventoryMovement::create([
                    'type' => 'out',
                    'product_id' => $item->product_id,
                    'color_id' => $item->color_id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_price,
                    'from_truck_id' => $dispatch->truck_id,
                    'note' => "Entrega Finalizada - Despacho #{$dispatch->dispatch_number}",
                    'created_by' => auth()->id(),
                    'source_type' => 'dispatch',
                    'source_id' => $dispatch->id,
                ]);
            }
            
            // Marcar pedidos como entregados/completados
            $dispatch->orders()->update(['status' => 'completed']);

            // Aquí se podría integrar la generación automática de facturas para cada pedido
            $invoiceService = app(InvoiceService::class);
            foreach ($dispatch->orders as $order) {
                $invoiceService->generateFromOrder($order);
            }
        });
    }

    /**
     * Cancela el despacho y revierte el stock.
     */
    public function cancel(Dispatch $dispatch): void
    {
        if ($dispatch->status === 'delivered') {
            throw ValidationException::withMessages(['status' => 'No se puede cancelar un despacho ya entregado.']);
        }

        DB::transaction(function () use ($dispatch) {
            if ($dispatch->status !== 'pending') {
                // Revertir movimientos de inventario si ya se inició
                InventoryMovement::where('source_type', 'dispatch')
                    ->where('source_id', $dispatch->id)
                    ->get()
                    ->each(fn ($m) => $m->delete());
            }

            $dispatch->update(['status' => 'pending']); // O cancelled si prefieres
            $dispatch->orders()->update(['status' => 'pending', 'dispatch_id' => null]);
            
            $dispatch->delete(); // O dejarlo en estado cancelado
        });
    }
}
