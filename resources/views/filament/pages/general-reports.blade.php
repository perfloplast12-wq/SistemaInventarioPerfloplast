<x-filament-panels::page>
    @php
        $d = $this->getReportData();
        $trendLabels = $d['dailySales']->map(function($v) { return \Illuminate\Support\Carbon::parse($v->date)->format('d/m'); })->values();
        $trendSales = $d['dailySales']->pluck('total')->values();
        $trendProd = $d['dailyProduction']->pluck('total')->values();
        $profitNames = $d['profitByProduct']->pluck('name')->values();
        $profitVals = $d['profitByProduct']->pluck('profit')->values();
        $topNames = $d['topProducts']->pluck('name')->values();
        $topProfits = $d['topProducts']->pluck('profit')->values();
        $dispLabels = $d['dailyDispatches']->map(function($v) { return \Illuminate\Support\Carbon::parse($v->date)->format('d/m'); })->values();
        $dispVals = $d['dailyDispatches']->pluck('total')->values();
    @endphp

    {{-- Datos para JS --}}
    <script id="rg-data" type="application/json">
        @json([
            'trendLabels' => $trendLabels,
            'trendSales' => $trendSales,
            'trendProd' => $trendProd,
            'profitNames' => $profitNames,
            'profitVals' => $profitVals,
            'topNames' => $topNames,
            'topProfits' => $topProfits,
            'dispLabels' => $dispLabels,
            'dispVals' => $dispVals,
            'earnings' => $d['earnings'],
        ])
    </script>

    <style>
        .rg-wrap{font-family:'Outfit',sans-serif;margin:-24px;padding:16px;min-height:100vh}
        .rg-hdr{background:linear-gradient(135deg,#4f46e5,#312e81);border-radius:16px;padding:20px 24px;color:#fff;margin-bottom:18px;box-shadow:0 8px 20px -6px rgba(79,70,229,.3);display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:14px}
        .rg-hdr h1{font-size:20px;font-weight:900;margin:0}.rg-hdr p{font-size:11px;opacity:.7;margin:3px 0 0}
        .rg-hdr .header-filters :deep(.fi-fo-field-wrp-label){display:none}
        .rg-hdr .header-filters :deep(.fi-input-wrp){background:rgba(255,255,255,.12)!important;border:1px solid rgba(255,255,255,.2)!important;border-radius:10px!important}
        .rg-hdr .header-filters :deep(input){color:#fff!important;font-weight:600!important;font-size:12px!important}
        .rg-kpis{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:18px}
        @media(min-width:1024px){.rg-kpis{grid-template-columns:repeat(4,1fr)}}
        .rg-kpi{background:#fff;padding:16px;border-radius:14px;border:1px solid #e2e8f0;border-left:5px solid var(--kc,#6366f1);transition:transform .2s}
        .rg-kpi:hover{transform:translateY(-2px)}.dark .rg-kpi{background:#0f172a;border-color:rgba(255,255,255,.06)}
        .rg-kpi-lbl{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b}
        .rg-kpi-val{font-size:22px;font-weight:900;color:#0f172a;margin:4px 0}.dark .rg-kpi-val{color:#f8fafc}
        .rg-kpi-tag{font-size:9px;font-weight:700;padding:3px 8px;border-radius:6px;display:inline-block}
        .rg-g{display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:18px}
        @media(min-width:1024px){.rg-g.g75{grid-template-columns:7fr 5fr}.rg-g.g66{grid-template-columns:1fr 1fr}.rg-g.g48{grid-template-columns:4fr 8fr}}
        .rg-c{background:#fff;border-radius:14px;padding:16px;border:1px solid #e2e8f0}.dark .rg-c{background:#0f172a;border-color:rgba(255,255,255,.06)}
        .rg-ct{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid #f1f5f9}
        .dark .rg-ct{border-bottom-color:rgba(255,255,255,.05)}
        .rg-p{display:flex;align-items:center;justify-content:space-between;padding:10px;border-radius:10px;border:1px solid #f1f5f9;margin-bottom:6px;transition:all .15s}
        .rg-p:hover{border-color:#e2e8f0;transform:translateX(2px)}.dark .rg-p{background:rgba(255,255,255,.02);border-color:rgba(255,255,255,.05)}
        .rg-av{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:11px;flex-shrink:0}
        .rg-nm{font-size:12px;font-weight:700;color:#334155}.dark .rg-nm{color:#e2e8f0}
        .rg-lk{font-size:10px;font-weight:800;text-transform:uppercase;padding:5px 10px;border-radius:8px;text-decoration:none;transition:all .15s;display:inline-flex;align-items:center;gap:3px}
        .rg-lk:hover{transform:scale(1.05)}
        .rg-lk-i{background:rgba(79,70,229,.1);color:#4f46e5}.rg-lk-b{background:rgba(59,130,246,.1);color:#3b82f6}
        .dark .rg-lk-i{background:rgba(99,102,241,.15);color:#818cf8}.dark .rg-lk-b{background:rgba(59,130,246,.15);color:#60a5fa}
        .rg-empty{text-align:center;padding:20px;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase}
        .dark .apexcharts-text{fill:#94a3b8!important}.dark .apexcharts-legend-text{color:#94a3b8!important}
        .dark .apexcharts-gridline{stroke:rgba(255,255,255,.04)!important}
        @media(max-width:640px){.rg-hdr{padding:14px}.rg-hdr h1{font-size:16px}.rg-kpi-val{font-size:18px}}
    </style>

    <div class="rg-wrap">
        <div class="rg-hdr">
            <div><h1>📊 Reporte General</h1><p>{{ $d['start_date'] }} — {{ $d['end_date'] }}</p></div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="header-filters" style="min-width:240px">{{ $this->form }}</div>
                <button wire:click="downloadPdf" class="bg-white/90 text-indigo-700 px-4 py-2 rounded-xl font-bold text-xs shadow hover:bg-white transition flex items-center gap-2 cursor-pointer">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4" /> PDF
                </button>
            </div>
        </div>

        <div class="rg-kpis">
            <div class="rg-kpi" style="--kc:#10b981"><div class="rg-kpi-lbl">Ventas Totales</div><div class="rg-kpi-val">Q {{ number_format($d['totalSales'], 0) }}</div><span class="rg-kpi-tag" style="background:#ecfdf5;color:#059669">Q {{ number_format($d['totalPaid'], 0) }} Cobrado</span></div>
            <div class="rg-kpi" style="--kc:#ef4444"><div class="rg-kpi-lbl">Costos Directos</div><div class="rg-kpi-val">Q {{ number_format($d['totalCosts'], 0) }}</div><span class="rg-kpi-tag" style="background:#fef2f2;color:#dc2626">Base Manufactura</span></div>
            <div class="rg-kpi" style="--kc:#3b82f6"><div class="rg-kpi-lbl">Utilidad Neta</div><div class="rg-kpi-val">Q {{ number_format($d['earnings'], 0) }}</div><span class="rg-kpi-tag" style="background:#eff6ff;color:#2563eb">Margen: {{ number_format($d['margenBruto'], 1) }}%</span></div>
            <div class="rg-kpi" style="--kc:#a855f7"><div class="rg-kpi-lbl">Ticket Promedio</div><div class="rg-kpi-val">Q {{ number_format($d['ticketPromedio'], 0) }}</div><span class="rg-kpi-tag" style="background:#faf5ff;color:#7e22ce">Eficiencia: {{ number_format($d['eficienciaCobranza'], 0) }}%</span></div>
        </div>

        {{-- CHARTS --}}
        <div class="rg-g g75" wire:ignore>
            <div class="rg-c"><div class="rg-ct">Flujo de Ventas Diarias</div><div id="rg-area-chart" style="min-height:280px"></div></div>
            <div class="rg-c"><div class="rg-ct">Distribución de Utilidad</div><div id="rg-donut-chart" style="min-height:280px"></div></div>
        </div>

        <div class="rg-g g48" wire:ignore>
            <div class="rg-c"><div class="rg-ct">Despachos Diarios</div><div id="rg-dispatch-chart" style="min-height:250px"></div></div>
            <div class="rg-c"><div class="rg-ct">Top 5 Productos Rentables</div><div id="rg-top-chart" style="min-height:250px"></div></div>
        </div>

        {{-- AUDITORÍA --}}
        <div class="rg-g g66">
            <div class="rg-c">
                <div class="rg-ct">Auditoría Vendedores</div>
                @forelse($d['salesByUser'] as $row)
                    <div class="rg-p">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="rg-av" style="background:rgba(79,70,229,.1);color:#6366f1">{{ strtoupper(substr($row->name, 0, 1)) }}</div>
                            <div class="min-w-0"><div class="rg-nm truncate">{{ $row->name }}</div><div class="text-[9px] font-bold text-slate-400 uppercase">{{ $row->count }} ventas</div></div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">Q {{ number_format($row->total_sales, 0) }}</span>
                            <a href="{{ url('/admin/sales?tableFilters[status][value]=confirmed&tableFilters[sale_date][from]=' . $d['start_raw'] . '&tableFilters[sale_date][until]=' . $d['end_raw'] . '&tableFilters[created_by][value]=' . $row->id) }}" class="rg-lk rg-lk-i">VER →</a>
                        </div>
                    </div>
                @empty
                    <div class="rg-empty">Sin ventas en este período</div>
                @endforelse
            </div>
            <div class="rg-c">
                <div class="rg-ct">Auditoría Pilotos</div>
                @forelse($d['dispatchesByDriver'] as $row)
                    <div class="rg-p">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="rg-av" style="background:rgba(59,130,246,.1);color:#3b82f6"><x-heroicon-o-truck class="w-4 h-4" /></div>
                            <div class="min-w-0"><div class="rg-nm truncate">{{ $row->driver_name }}</div><div class="text-[9px] font-bold text-slate-400 uppercase">{{ $row->count }} viajes</div></div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-sm font-black text-blue-600 dark:text-blue-400">{{ $row->count }}</span>
                            @if($row->driver_id)
                                <a href="{{ url('/admin/dispatches?tableFilters[created_at][from]=' . $d['start_raw'] . '&tableFilters[created_at][until]=' . $d['end_raw'] . '&tableFilters[driver_id][value]=' . $row->driver_id) }}" class="rg-lk rg-lk-b">AUDITAR →</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rg-empty">Sin despachos en este período</div>
                @endforelse
            </div>
        </div>

        {{-- PRODUCCIÓN --}}
        @if($d['productionDetailed']->count() > 0)
        <div class="rg-g" style="margin-top:2px">
            <div class="rg-c" style="overflow-x:auto">
                <div class="rg-ct">Producción por Turno</div>
                <table style="width:100%;border-collapse:collapse;font-size:12px">
                    <thead><tr style="border-bottom:2px solid #e2e8f0">
                        <th style="text-align:left;padding:6px 10px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b">Turno</th>
                        <th style="text-align:left;padding:6px 10px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b">Producto</th>
                        <th style="text-align:right;padding:6px 10px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b">Cantidad</th>
                        <th style="text-align:right;padding:6px 10px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b">Eficiencia</th>
                    </tr></thead>
                    <tbody>
                        @foreach($d['productionDetailed'] as $row)
                        <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:6px 10px;font-weight:700" class="dark:text-slate-300">{{ $row->shift_name }}</td><td style="padding:6px 10px;color:#64748b" class="dark:text-slate-400">{{ $row->product_name }}</td><td style="padding:6px 10px;text-align:right;font-weight:800" class="dark:text-white">{{ number_format($row->total_qty) }}</td><td style="padding:6px 10px;text-align:right">@if($row->eficiencia !== null)<span style="font-weight:800;font-size:10px;padding:2px 6px;border-radius:6px;{{ $row->eficiencia >= 100 ? 'background:#ecfdf5;color:#059669' : ($row->eficiencia >= 70 ? 'background:#fefce8;color:#ca8a04' : 'background:#fef2f2;color:#dc2626') }}">{{ number_format($row->eficiencia, 0) }}%</span>@else<span style="color:#94a3b8;font-size:10px">—</span>@endif</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ALL CHARTS JS - Outside of Alpine to avoid escaping issues --}}
    @script
    <script>
    (function() {
        var raw = document.getElementById('rg-data');
        if (!raw) return;
        var D = JSON.parse(raw.textContent);
        var isDark = document.documentElement.classList.contains('dark');
        var gridC = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)';
        var mode = isDark ? 'dark' : 'light';

        function waitApex(fn) {
            if (typeof ApexCharts !== 'undefined') fn();
            else setTimeout(function(){ waitApex(fn); }, 200);
        }

        waitApex(function() {
            // 1. Area Chart - Flujo de Ventas
            var areaEl = document.getElementById('rg-area-chart');
            if (areaEl && !areaEl._done) {
                new ApexCharts(areaEl, {
                    series: [{ name: 'Ventas (Q)', data: D.trendSales }],
                    chart: { type: 'area', height: 270, toolbar:{show:false}, fontFamily:'Outfit', background:'transparent', sparkline:{enabled:false} },
                    theme: { mode: mode },
                    colors: ['#6366f1'],
                    stroke: { curve: 'smooth', width: 3 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                    grid: { borderColor: gridC, strokeDashArray: 3 },
                    xaxis: { categories: D.trendLabels, labels: { style: { fontWeight: 700, fontSize: '10px' } } },
                    yaxis: { labels: { formatter: function(v){ return 'Q ' + Math.round(v).toLocaleString(); }, style: { fontWeight: 700 } } },
                    dataLabels: { enabled: false },
                    tooltip: { theme: 'dark', y: { formatter: function(v){ return 'Q ' + Number(v).toLocaleString(); } } }
                }).render();
                areaEl._done = true;
            }

            // 2. Donut - Utilidad
            var donutEl = document.getElementById('rg-donut-chart');
            if (donutEl && !donutEl._done) {
                if (!D.profitVals.length || D.profitVals.every(function(v){return v<=0;})) {
                    donutEl.innerHTML = '<div style="text-align:center;padding:80px 20px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase">Sin datos de utilidad</div>';
                } else {
                    new ApexCharts(donutEl, {
                        series: D.profitVals,
                        chart: { type: 'donut', height: 270, fontFamily: 'Outfit' },
                        theme: { mode: mode },
                        labels: D.profitNames,
                        colors: ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'],
                        legend: { position: 'bottom', fontWeight: 700 },
                        plotOptions: { pie: { donut: { size: '60%', labels: { show: true, name: { show: true }, value: { show: true, formatter: function(v){ return 'Q ' + Number(v).toLocaleString(); } }, total: { show: true, label: 'UTILIDAD', formatter: function(){ return 'Q ' + Math.round(D.earnings).toLocaleString(); } } } } } },
                        dataLabels: { enabled: false }
                    }).render();
                }
                donutEl._done = true;
            }

            // 3. Dispatch bar chart
            var dispEl = document.getElementById('rg-dispatch-chart');
            if (dispEl && !dispEl._done) {
                if (!D.dispVals.length) {
                    dispEl.innerHTML = '<div style="text-align:center;padding:60px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase">Sin despachos</div>';
                } else {
                    new ApexCharts(dispEl, {
                        series: [{ name: 'Despachos', data: D.dispVals }],
                        chart: { type: 'bar', height: 240, toolbar:{show:false}, fontFamily:'Outfit' },
                        theme: { mode: mode },
                        colors: ['#3b82f6'],
                        plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
                        grid: { borderColor: gridC },
                        xaxis: { categories: D.dispLabels, labels: { style: { fontWeight: 700 } } },
                        dataLabels: { enabled: true, style: { fontWeight: 900 } }
                    }).render();
                }
                dispEl._done = true;
            }

            // 4. Top products horizontal bar
            var topEl = document.getElementById('rg-top-chart');
            if (topEl && !topEl._done) {
                if (!D.topNames.length) {
                    topEl.innerHTML = '<div style="text-align:center;padding:60px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase">Sin productos vendidos</div>';
                } else {
                    new ApexCharts(topEl, {
                        series: [{ name: 'Utilidad', data: D.topProfits }],
                        chart: { type: 'bar', height: 240, toolbar:{show:false}, fontFamily:'Outfit' },
                        theme: { mode: mode },
                        colors: ['#10b981','#6366f1','#f59e0b','#ef4444','#8b5cf6'],
                        plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '50%', distributed: true } },
                        xaxis: { categories: D.topNames },
                        dataLabels: { enabled: true, formatter: function(v){ return 'Q ' + Math.round(v).toLocaleString(); }, style: { fontWeight: 900 } },
                        grid: { borderColor: gridC },
                        legend: { show: false }
                    }).render();
                }
                topEl._done = true;
            }
        });
    })();
    </script>
    @endscript
</x-filament-panels::page>
