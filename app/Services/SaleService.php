<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /**
     * Confirma la venta, genera movimientos de salida y descuenta stock.
     */
    public function confirm(Sale $sale): void
    {
        if ($sale->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Solo se pueden confirmar ventas en borrador.']);
        }

        // 1. Recalcular totales antes de validar
        $this->recalculateTotals($sale);

        // 2. Validaciones básicas
        if (empty($sale->customer_name)) {
            throw ValidationException::withMessages(['customer_name' => 'El nombre del cliente es requerido.']);
        }

        if (!$sale->from_warehouse_id && !$sale->from_truck_id) {
            throw ValidationException::withMessages(['origin' => 'Debe seleccionar un origen (Bodega o Camión).']);
        }

        if ($sale->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'La venta debe tener al menos un producto.']);
        }

        DB::transaction(function () use ($sale) {
            // Eager load items and products within the transaction for consistent state
            $sale->load(['items.product', 'items.color']);

            // 3. Validar Stock Estricto (Dentro de la transacción para evitar race conditions)
            foreach ($sale->items as $item) {
                $product = $item->product;
                
                $stockQuery = \App\Models\Stock::where('product_id', $item->product_id)
                    ->where('color_id', $item->color_id);
                    
                if ($sale->from_warehouse_id) {
                    $stockQuery->where('warehouse_id', $sale->from_warehouse_id);
                } else {
                    $stockQuery->where('truck_id', $sale->from_truck_id);
                }
                
                // Bloqueamos para lectura el stock para asegurar que nadie más lo use mientras confirmamos
                $available = (float) $stockQuery->lockForUpdate()->value('quantity') ?? 0;
                
                if ($available < (float)$item->quantity) {
                    $colorName = $item->color ? $item->color->name : 'N/A';
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para '{$product->name}' color '{$colorName}'. Disponible: " . number_format($available, 2) . ", Requerido: " . number_format($item->quantity, 2)
                    ]);
                }
            }

            // 4. Crear movimientos de inventario (OUT)
            foreach ($sale->items as $item) {
                InventoryMovement::create([
                    'type'              => 'out',
                    'product_id'        => $item->product_id,
                    'color_id'          => $item->color_id,
                    'quantity'          => $item->quantity,
                    'unit_cost'         => $item->product->cost_price ?? 0,
                    'from_warehouse_id' => $sale->from_warehouse_id,
                    'from_truck_id'     => $sale->from_truck_id,
                    'note'              => ($sale->origin_type === 'warehouse' ? "Pre-venta de Bodega" : "Venta Directa de Camión") . " - #{$sale->sale_number}",
                    'created_by'        => auth()->id(),
                    'source_type'       => 'sale',
                    'source_id'         => $sale->id,
                ]);
            }

            // 5. Actualizar estado
            $sale->update(['status' => 'confirmed']);

            // 6. GENERACIÓN AUTOMÁTICA DE PEDIDO (Logística)
            // Esto permite que el área de despachos vea la venta como un pedido pendiente
            $order = \App\Models\Order::create([
                'sale_id'          => $sale->id,
                'customer_name'    => $sale->customer_name,
                'customer_nit'     => $sale->customer_nit ?? 'C/F',
                'delivery_address' => $sale->delivery_address,
                'phone'            => $sale->phone,
                'order_date'       => $sale->sale_date,
                'payment_method'   => $sale->payments->first()?->payment_method ?? 'cash',
                'payment_status'   => $sale->balance <= 0 ? 'paid' : 'partial',
                'notes'            => "Generado automáticamente desde Preventa #{$sale->sale_number}. " . $sale->note,
                'status'           => $sale->origin_type === 'warehouse' ? 'pending' : 'shipped',
                'created_by'       => $sale->created_by,
            ]);

            foreach ($sale->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'color_id'   => $item->color_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal'   => $item->subtotal,
                ]);
            }

            // 7. Generar Factura
            app(InvoiceService::class)->generateFromSale($sale);
        });
    }

    /**
     * Cancela la venta y revierte el stock eliminando los movimientos.
     */
    public function cancel(Sale $sale): void
    {
        if ($sale->status !== 'confirmed') {
            if ($sale->status === 'draft') {
                $sale->update(['status' => 'cancelled']);
                return;
            }
            throw ValidationException::withMessages(['status' => 'Estado no cancelable.']);
        }

        DB::transaction(function () use ($sale) {
            // 1. Revertir stock (Eliminar movimientos asociados)
            InventoryMovement::where('source_type', 'sale')
                ->where('source_id', $sale->id)
                ->get()
                ->each(fn ($m) => $m->delete());

            // 2. CANCELACIÓN AUTOMÁTICA DEL PEDIDO (Logística)
            // Si existe un pedido vinculado, lo cancelamos para que el piloto no lo entregue
            Order::where('sale_id', $sale->id)
                ->update(['status' => 'cancelled']);

            $sale->update(['status' => 'cancelled']);
        });
    }

    /**
     * Calcula Subtotal, Descuento y Total
     */
    public function recalculateTotals(Sale $sale): void
    {
        $subtotal = $sale->items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_price);
        
        $discountAmount = 0;
        if ($sale->discount_type === 'percent') {
            $discountAmount = $subtotal * (min(100, max(0, (float)$sale->discount_value)) / 100);
        } elseif ($sale->discount_type === 'fixed') {
            $discountAmount = min($subtotal, max(0, (float)$sale->discount_value));
        }

        $total = max(0, $subtotal - $discountAmount);

        $sale->update([
            'discount_amount' => $discountAmount,
            'total' => $total,
        ]);
        
        // Actualizar subtotales de items si no están sincronizados
        foreach ($sale->items as $item) {
            $itemSubtotal = (float)$item->quantity * (float)$item->unit_price;
            if (abs((float)$item->subtotal - $itemSubtotal) > 0.001) {
                $item->update(['subtotal' => $itemSubtotal]);
            }
        }
    }

    /**
     * Registra un pago y valida balance
     */
    public function recordPayment(Sale $sale, array $data): void
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'El monto debe ser mayor a 0.']);
        }

        if ($amount > ($sale->balance + 0.01)) {
             throw ValidationException::withMessages(['amount' => 'El pago excede el saldo pendiente (Q ' . number_format($sale->balance, 2) . ').']);
        }

        $sale->payments()->create([
            'payment_method' => $data['method'],
            'amount' => $amount,
            'payment_date' => $data['payment_date'] ?? now(),
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
