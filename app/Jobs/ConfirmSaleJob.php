<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\InventoryMovement;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ConfirmSaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sale;

    /**
     * Create a new job instance.
     */
    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sale = $this->sale;

        DB::transaction(function () use ($sale) {
            $sale->loadMissing(['items.product', 'items.color', 'payments']);

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
                    'created_by'        => $sale->created_by,
                    'source_type'       => 'sale',
                    'source_id'         => $sale->id,
                ]);
            }

            // 6. GENERACIÓN AUTOMÁTICA DE PEDIDO (Logística)
            $order = Order::create([
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
                'lat'              => $sale->lat,
                'lng'              => $sale->lng,
                'created_by'       => $sale->created_by,
            ]);

            $orderItemsData = [];
            $now = now();
            foreach ($sale->items as $item) {
                $orderItemsData[] = [
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'color_id'   => $item->color_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal'   => $item->subtotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            OrderItem::insert($orderItemsData); // Insert en lote para evitar múltiples queries

            // 7. Generar Factura
            app(InvoiceService::class)->generateFromSale($sale);
        });
    }
}
