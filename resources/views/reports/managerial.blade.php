<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Financiero y Operativo</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1e293b; margin: 0; padding: 0; font-size: 10px; line-height: 1.4; }
        
        /* Header Exacto */
        .header { background: #1e1b4b; color: white; padding: 35px 30px; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 26px; text-transform: uppercase; letter-spacing: 1px; font-weight: 900; }
        .header h2 { margin: 5px 0 0; font-size: 13px; font-weight: 300; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.5px; }
        .header .period { margin-top: 20px; font-size: 11px; font-weight: bold; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 4px; display: inline-block; }
        
        .gen-date { text-align: right; font-size: 9px; color: #64748b; margin-top: -15px; margin-bottom: 25px; font-weight: bold; }
        
        .section-title { font-size: 13px; font-weight: 900; margin: 25px 0 15px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 8px; text-transform: uppercase; color: #1e293b; }
        
        /* KPIs Style Screenshots */
        .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; table-layout: fixed; }
        .kpi-td { width: 25%; padding: 0 8px 0 0; vertical-align: top; box-sizing: border-box; }
        .kpi-td:last-child { padding-right: 0; }
        .kpi-card { background: #ffffff; padding: 15px 8px; border-radius: 6px; border: 1px solid #e2e8f0; border-left-width: 5px; box-sizing: border-box; }
        .kpi-label { font-size: 8px; color: #64748b; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; }
        .kpi-value { font-size: 16px; font-weight: 900; color: #0f172a; }

        .border-emerald { border-left-color: #10b981; }
        .border-red { border-left-color: #ef4444; }
        .border-blue { border-left-color: #3b82f6; }
        .border-purple { border-left-color: #8b5cf6; }

        /* Tables screenshot style */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8fafc; color: #475569; padding: 10px 12px; text-align: left; font-size: 9px; font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 10px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-black { font-weight: 900; }
        .text-red { color: #ef4444; }
        .bg-gray { background: #f8fafc; }
        
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE FINANCIERO Y OPERATIVO</h1>
        <h2>{{ config('app.name', 'PERFLO-PLAST') }} - INDICADORES GERENCIALES</h2>
        <div class="period">PERÍODO ANALIZADO: {{ $start_date }} AL {{ $end_date }}</div>
    </div>

    <div class="gen-date">Documento Oficial Generado: {{ now()->format('d/m/Y H:i:s') }}</div>

    <div class="section-title">1. RESUMEN EJECUTIVO (KPIS)</div>
    <table class="kpi-table">
        <tr>
            <td class="kpi-td">
                <div class="kpi-card border-emerald">
                    <div class="kpi-label">INGRESOS BRUTOS</div>
                    <div class="kpi-value">Q {{ number_format($totalSales, 2) }}</div>
                </div>
            </td>
            <td class="kpi-td">
                <div class="kpi-card border-red">
                    <div class="kpi-label">COSTOS DIRECTOS EST.</div>
                    <div class="kpi-value">Q {{ number_format($totalCosts, 2) }}</div>
                </div>
            </td>
            <td class="kpi-td">
                <div class="kpi-card border-blue">
                    <div class="kpi-label">UTILIDAD NETA</div>
                    <div class="kpi-value">Q {{ number_format($earnings, 2) }}</div>
                </div>
            </td>
            <td class="kpi-td">
                <div class="kpi-card border-purple">
                    <div class="kpi-label">MARGEN DE RENTABILIDAD</div>
                    <div class="kpi-value">{{ number_format($margenBruto, 2) }}%</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">2. DETALLES FINANCIEROS Y RENDIMIENTO DE CARTERA</div>
    <table>
        <thead>
            <tr>
                <th style="width: 75%;">INDICADOR MÉTRICO</th>
                <th style="width: 25%; text-align: right;">VALOR ALCANZADO</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Ticket Promedio por Venta Múltiple</td><td class="text-right font-black">Q {{ number_format($ticketPromedio, 2) }}</td></tr>
            <tr><td>Total Pagado (Liquidez Recibida)</td><td class="text-right font-black">Q {{ number_format($totalPaid, 2) }}</td></tr>
            <tr><td class="text-red">Cuentas Pendientes (Por Cobrar)</td><td class="text-right font-black text-red">Q {{ number_format($totalPending, 2) }}</td></tr>
            <tr><td>Margen Bruto de Ganancia (Porcentaje Operativo)</td><td class="text-right font-black">{{ number_format($margenBruto, 2) }}%</td></tr>
            <tr><td>Eficiencia de Cobranza sobre Ventas</td><td class="text-right font-black">{{ number_format($eficienciaCobranza, 2) }}%</td></tr>
        </tbody>
    </table>

    <div class="section-title">3. TOP 5 PRODUCTOS MÁS RENTABLES DEL PERÍODO</div>
    <table>
        <thead>
            <tr>
                <th>CATEGORÍA / PRODUCTO</th>
                <th class="text-center">UDS. DESCARGADAS</th>
                <th class="text-right">INGRESO DEVENGADO</th>
                <th class="text-right">APORTE UTILIDAD NETA</th>
                <th class="text-right">MARGEN DIRECTO(%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topProducts as $item)
            <tr>
                <td class="font-black">{{ strtoupper($item->name) }}</td>
                <td class="text-center">{{ number_format($item->qty, 0) }}</td>
                <td class="text-right">Q {{ number_format($item->total, 2) }}</td>
                <td class="text-right font-black">Q {{ number_format($item->profit, 2) }}</td>
                <td class="text-right">{{ number_format($item->margin_pct, 2) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">4. PRODUCTIVIDAD Y EFICACIA POR TURNO</div>
    <table>
        <thead>
            <tr>
                <th>JORNADA / TURNO</th>
                <th>VARIANTE / ITEM FABRICADO</th>
                <th class="text-center">OPERACIONES</th>
                <th class="text-center">VOL. FÍSICO TOTAL</th>
                <th class="text-right">EFICIENCIA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productionDetailed as $row)
            <tr>
                <td class="font-black">{{ strtoupper($row->shift_name) }}</td>
                <td>{{ $row->product_name }}</td>
                <td class="text-center">{{ $row->operations }} r.</td>
                <td class="text-center">{{ number_format($row->total_qty, 0) }} u.</td>
                <td class="text-right font-black @if($row->eficiencia < 70) text-red @endif">
                    {{ is_null($row->eficiencia) ? 'N/A' : number_format($row->eficiencia, 2) . '%' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="page-break-before: always;"></div>

    <div class="section-title">5. RENDIMIENTO VENDEDORES</div>
    <table>
        <thead>
            <tr>
                <th style="width: 75%;">ASESOR / AGENTE</th>
                <th style="width: 25%; text-align: right;">FACTURADO (Q)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesByUser as $row)
            <tr>
                <td class="font-black">{{ $row->name }} ({{ $row->count }} v.)</td>
                <td class="text-right font-black">Q {{ number_format($row->total_sales, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">6. DESPACHOS LOGÍSTICOS</div>
    <table>
        <thead>
            <tr>
                <th style="width: 75%;">PILOTO / TRANSPORTISTA</th>
                <th style="width: 25%; text-align: right;">VIAJES</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dispatchesByDriver as $row)
            <tr>
                <td class="font-black">{{ strtoupper($row->driver_name) }}</td>
                <td class="text-right font-black">{{ $row->count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        DOCUMENTO FINANCIERO CONFIDENCIAL &nbsp; | &nbsp; ERP SISTEMA CORE &nbsp; | &nbsp; {{ config('app.name') }}
    </div>
</body>
</html>
