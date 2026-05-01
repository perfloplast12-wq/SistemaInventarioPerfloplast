<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Genera una factura a partir de una Venta confirmada.
     */
    public function generateFromSale(Sale $sale): Invoice
    {
        return DB::transaction(function () use ($sale) {
            $invoice = Invoice::create([
                'sale_id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'customer_nit' => $sale->customer_nit ?? 'C/F',
                'invoice_date' => now(),
                'payment_method' => $this->getPaymentMethodLabel($sale),
                'sale_type' => ($sale->origin_type === 'warehouse') ? 'Preventa (Bodega)' : 'Venta en Ruta (Camión)',
                'subtotal' => $sale->total + $sale->discount_amount,
                'discount_amount' => $sale->discount_amount,
                'total' => $sale->total,
                'amount_paid' => $sale->payments->sum('amount'),
                'change_amount' => max(0, $sale->payments->sum('amount') - $sale->total),
                'created_by' => auth()->id(),
            ]);

            foreach ($sale->items as $item) {
                $invoice->items()->create([
                    'product_code' => $item->product->sku ?? ('N/A-' . strtoupper(str_replace(' ', '-', $item->product->name))),
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->subtotal,
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Genera una factura a partir de un Pedido entregado.
     */
    public function generateFromOrder(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $subtotal = $order->items->sum('subtotal');
            
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_nit' => $order->customer_nit ?? 'C/F',
                'invoice_date' => now(),
                'payment_method' => $this->getOrderPaymentMethodLabel($order->payment_method),
                'sale_type' => 'Pedido / Despacho',
                'subtotal' => $subtotal,
                'discount_amount' => 0, // Por ahora no manejamos descuentos en pedidos
                'total' => $subtotal,
                'amount_paid' => $order->payment_status === 'paid' ? $subtotal : 0,
                'change_amount' => 0,
                'created_by' => auth()->id(),
            ]);

            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'product_code' => $item->product->sku ?? ('N/A-' . strtoupper(str_replace(' ', '-', $item->product->name))),
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->subtotal,
                ]);
            }

            return $invoice;
        });
    }

    private function getPaymentMethodLabel(Sale $sale): string
    {
        $method = $sale->payments->first()?->method;
        return match ($method) {
            'cash' => 'EFECTIVO',
            'transfer' => 'TRANSFERENCIA',
            'card' => 'TARJETA',
            default => 'OTRO',
        };
    }

    private function getOrderPaymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'EFECTIVO',
            'transfer' => 'TRANSFERENCIA',
            'card' => 'TARJETA',
            'cod' => 'CONTRA ENTREGA',
            default => 'OTRO',
        };
    }
}
