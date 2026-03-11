<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de Venta - {{ $sale->sale_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #2563eb; }
        .invoice-title { font-size: 18px; font-weight: bold; margin-top: 5px; }
        
        .info-grid { width: 100%; margin-bottom: 20px; }
        .info-box { width: 48%; display: inline-block; vertical-align: top; }
        .label { font-weight: bold; color: #666; font-size: 10px; text-transform: uppercase; }
        .value { font-size: 12px; margin-bottom: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 10px; text-align: left; font-size: 11px; color: #64748b; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
        
        .totals { float: right; width: 300px; }
        .total-row { padding: 5px 0; border-bottom: 1px solid #eee; }
        .total-label { width: 150px; display: inline-block; font-size: 12px; }
        .total-value { width: 140px; display: inline-block; text-align: right; font-weight: bold; font-size: 12px; }
        .grand-total { border-top: 2px solid #2563eb; padding-top: 10px; margin-top: 5px; font-size: 16px; color: #2563eb; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-success { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">PERFLO-PLAST</div>
        <div class="invoice-title">COMPROBANTE DE VENTA</div>
        <div style="font-size: 12px; color: #64748b;">Nro Operación: {{ $sale->sale_number }}</div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="label">Cliente:</div>
            <div class="value" style="font-size: 14px; font-weight: bold;">{{ $sale->customer_name }}</div>
            <div class="label">Punto de Venta/Bodega:</div>
            <div class="value">{{ $sale->fromWarehouse?->name ?? $sale->fromTruck?->name ?? 'Venta Directa' }}</div>
        </div>
        <div class="info-box" style="text-align: right;">
            <div class="label">Fecha de Emisión:</div>
            <div class="value">{{ $sale->sale_date->format('d/m/Y H:i') }}</div>
            <div class="label">Vendedor:</div>
            <div class="value">{{ $sale->creator?->name ?? 'Sistema' }}</div>
            <div class="badge badge-success">ESTADO: {{ strtoupper($sale->status) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Descripción del Producto</th>
                <th style="text-align: center;">Cant.</th>
                <th style="text-align: right;">Precio Unit.</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product?->name }}</td>
                <td style="text-align: center;">{{ number_format($item->quantity, 2) }}</td>
                <td style="text-align: right;">Q {{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right;">Q {{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="width: 100%; border-top: 1px solid #eee; padding-top: 20px;">
        <div style="width: 50%; display: inline-block; vertical-align: top;">
            <div class="label">Resumen de Pagos:</div>
            @forelse($sale->payments as $payment)
                <div style="font-size: 11px;">
                    • {{ $payment->payment_date->format('d/m/Y') }} - {{ strtoupper($payment->method) }}: 
                    <span style="font-weight: bold;">Q {{ number_format($payment->amount, 2) }}</span>
                </div>
            @empty
                <div style="font-size: 11px; color: #94a3b8;">Sin pagos registrados</div>
            @endforelse
            
            @if($sale->note)
                <div class="label" style="margin-top: 15px;">Observaciones:</div>
                <div style="font-size: 11px; color: #475569;">{{ $sale->note }}</div>
            @endif
        </div>
        
        <div class="totals">
            <div class="total-row">
                <span class="total-label">Subtotal Bruto:</span>
                <span class="total-value">Q {{ number_format($sale->items->sum('subtotal'), 2) }}</span>
            </div>
            @if($sale->discount_amount > 0)
            <div class="total-row" style="color: #dc2626;">
                <span class="total-label">Descuento:</span>
                <span class="total-value">- Q {{ number_format($sale->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="total-row grand-total">
                <span class="total-label" style="font-weight: black;">TOTAL A PAGAR:</span>
                <span class="total-value" style="font-size: 18px;">Q {{ number_format($sale->total, 2) }}</span>
            </div>
            <div class="total-row" style="border: none;">
                <span class="total-label">Pagado:</span>
                <span class="total-value">Q {{ number_format($sale->total_paid, 2) }}</span>
            </div>
            <div class="total-row" style="border: none; color: #166534; font-weight: bold;">
                <span class="total-label">Saldo Pendiente:</span>
                <span class="total-value">Q {{ number_format($sale->balance, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        Perflo-Plast - Calidad y Resistencia en Productos Plásticos<br>
        Este documento es un comprobante de operación interna generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
