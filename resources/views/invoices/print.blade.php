<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura_{{ $invoice->invoice_number }}</title>
    <style>
        @page { size: portrait; margin: 1cm; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.5; margin: 0; padding: 20px; background: #f9fafb; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #e5e7eb; background: #fff; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-radius: 8px; }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 2px solid #f3f4f6; padding-bottom: 20px; }
        .company-info h1 { margin: 0; color: #f97316; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; }
        .company-details { font-size: 11px; color: #6b7280; margin-top: 5px; }
        
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0; font-size: 18px; color: #111827; }
        .invoice-number { font-size: 20px; font-weight: 700; color: #f97316; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .info-section h3 { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.05em; margin-bottom: 10px; border-bottom: 1px solid #f3f4f6; padding-bottom: 4px; }
        .info-item { margin-bottom: 4px; }
        .info-label { font-weight: 600; min-width: 100px; display: inline-block; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f9fafb; color: #374151; font-weight: 700; text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; font-size: 10px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        .footer { display: flex; justify-content: flex-end; }
        .totals-box { width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; }
        .total-row.grand-total { border-top: 2px solid #f97316; margin-top: 10px; padding-top: 12px; color: #f97316; font-size: 16px; font-weight: 800; }

        .bottom-note { margin-top: 60px; text-align: center; color: #9ca3af; font-size: 10px; }
        
        .no-print { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; }
        .btn-print { background: #f97316; color: white; }
        .btn-print:hover { background: #ea580c; }
        .btn-back { background: #6b7280; color: white; }

        @media print {
            body { background: white; padding: 0; }
            .invoice-box { box-shadow: none; border: none; padding: 0; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="javascript:history.back()" class="btn btn-back">Volver</a>
        <button onclick="window.print()" class="btn btn-print">Imprimir Factura</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="company-info">
                <h1>{{ config('company.name') }}</h1>
                <div class="company-details">
                    <div>{{ config('company.address') }}</div>
                    <div>Tel: {{ config('company.phone') }} | Email: {{ config('company.email') }}</div>
                    <div>NIT: {{ config('company.nit') }}</div>
                </div>
            </div>
            <div class="invoice-title">
                <h2>FACTURA / RECIBO</h2>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-section">
                <h3>Información de Factura</h3>
                <div class="info-item"><span class="info-label">Fecha:</span> {{ $invoice->invoice_date->format('d/m/Y H:i') }}</div>
                <div class="info-item"><span class="info-label">Método Pago:</span> {{ $invoice->payment_method }}</div>
                <div class="info-item"><span class="info-label">Tipo Venta:</span> {{ $invoice->sale_type }}</div>
            </div>
            <div class="info-section">
                <h3>Datos del Cliente</h3>
                <div class="info-item"><span class="info-label">Nombre:</span> {{ $invoice->customer_name }}</div>
                <div class="info-item"><span class="info-label">NIT:</span> {{ $invoice->customer_nit }}</div>
                <div class="info-item"><span class="info-label">Atendido por:</span> {{ $invoice->creator?->name }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->product_code }}</td>
                        <td class="font-bold">{{ $item->product_name }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">Q {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right font-bold">Q {{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <div class="totals-box">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>Q {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if ($invoice->discount_amount > 0)
                    <div class="total-row">
                        <span>Descuento:</span>
                        <span>- Q {{ number_format($invoice->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="total-row grand-total">
                    <span>TOTAL:</span>
                    <span>Q {{ number_format($invoice->total, 2) }}</span>
                </div>
                <div class="total-row" style="margin-top: 10px;">
                    <span>Monto Pagado:</span>
                    <span>Q {{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                @if ($invoice->change_amount > 0)
                    <div class="total-row">
                        <span>Cambio:</span>
                        <span>Q {{ number_format($invoice->change_amount, 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="bottom-note">
            <p>¡Gracias por su compra!</p>
            <p>Esta factura fue generada electrónicamente el {{ now()->format('d/m/Y H:i') }}</p>
            <p>Sistema de Inventario y Distribución v1.0</p>
        </div>
    </div>
</body>
</html>
