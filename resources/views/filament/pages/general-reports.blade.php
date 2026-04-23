<x-filament-panels::page>
    @php $d = $this->getReportData(); @endphp

    <style>
        .rg-wrap { font-family: 'Outfit', sans-serif; margin: -24px; padding: 16px; min-height: 100vh; }
        .rg-header { background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%); border-radius: 16px; padding: 24px 28px; color: white; margin-bottom: 20px; box-shadow: 0 8px 24px -6px rgba(79,70,229,0.35); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; }
        .rg-header h1 { font-size: 22px; font-weight: 900; margin: 0; letter-spacing: -0.02em; }
        .rg-header p { font-size: 12px; opacity: 0.75; margin: 4px 0 0; }
        .rg-header .header-filters :deep(.fi-fo-field-wrp-label) { display: none; }
        .rg-header .header-filters :deep(.fi-input-wrp) { background: rgba(255,255,255,0.12) !important; border: 1px solid rgba(255,255,255,0.2) !important; border-radius: 10px !important; }
        .rg-header .header-filters :deep(input) { color: white !important; font-weight: 600 !important; font-size: 12px !important; }

        .rg-kpis { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 20px; }
        @media (min-width: 1024px) { .rg-kpis { grid-template-columns: repeat(4, 1fr); } }
        .rg-kpi { background: white; padding: 18px; border-radius: 14px; border: 1px solid #e2e8f0; border-left: 5px solid var(--kc, #6366f1); position: relative; overflow: hidden; transition: transform .2s; }
        .rg-kpi:hover { transform: translateY(-2px); }
        .dark .rg-kpi { background: #0f172a; border-color: rgba(255,255,255,0.06); }
        .rg-kpi-lbl { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
        .rg-kpi-val { font-size: 24px; font-weight: 900; color: #0f172a; margin: 6px 0; }
        .dark .rg-kpi-val { color: #f8fafc; }
        .rg-kpi-tag { font-size: 9px; font-weight: 800; padding: 3px 8px; border-radius: 6px; display: inline-block; }

        .rg-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 20px; }
        @media (min-width: 1024px) { .rg-grid.g-7-5 { grid-template-columns: 7fr 5fr; } .rg-grid.g-6-6 { grid-template-columns: 1fr 1fr; } .rg-grid.g-12 { grid-template-columns: 1fr; } }

        .rg-card { background: white; border-radius: 14px; padding: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .dark .rg-card { background: #0f172a; border-color: rgba(255,255,255,0.06); }
        .rg-card-title { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
        .rg-card-title::after { content: ''; flex: 1; height: 1px; background: #f1f5f9; }
        .dark .rg-card-title { color: #64748b; border-bottom-color: rgba(255,255,255,0.05); }
        .dark .rg-card-title::after { background: rgba(255,255,255,0.05); }

        .rg-person { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 10px; border: 1px solid #f1f5f9; margin-bottom: 8px; transition: all .15s; }
        .rg-person:hover { border-color: #e2e8f0; transform: translateX(2px); }
        .dark .rg-person { background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05); }
        .dark .rg-person:hover { border-color: rgba(255,255,255,0.1); }
        .rg-avatar { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 12px; flex-shrink: 0; }
        .rg-name { font-size: 12px; font-weight: 700; color: #334155; }
        .dark .rg-name { color: #e2e8f0; }

        .rg-link { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; padding: 5px 10px; border-radius: 8px; text-decoration: none; transition: all .15s; display: inline-flex; align-items: center; gap: 4px; }
        .rg-link:hover { transform: scale(1.05); }
        .rg-link-indigo { background: rgba(79,70,229,0.1); color: #4f46e5; }
        .rg-link-blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .dark .rg-link-indigo { background: rgba(99,102,241,0.15); color: #818cf8; }
        .dark .rg-link-blue { background: rgba(59,130,246,0.15); color: #60a5fa; }

        .rg-empty { text-align: center; padding: 24px; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }

        .rg-chart-scroll { overflow-x: auto; scrollbar-width: none; }
        .rg-chart-scroll::-webkit-scrollbar { display: none; }

        /* Dark mode ApexCharts global */
        .dark .apexcharts-text { fill: #94a3b8 !important; }
        .dark .apexcharts-legend-text { color: #94a3b8 !important; }
        .dark .apexcharts-gridline { stroke: rgba(255,255,255,0.04) !important; }
        .dark .apexcharts-tooltip { background: #1e293b !important; border-color: #334155 !important; }
        .dark .apexcharts-tooltip-title { background: #0f172a !important; border-bottom-color: #334155 !important; }

        @media (max-width: 640px) {
            .rg-header { padding: 16px; }
            .rg-header h1 { font-size: 18px; }
            .rg-kpi-val { font-size: 20px; }
            .rg-person { padding: 8px 10px; }
        }
    </style>

    <div class="rg-wrap">
        {{-- HEADER --}}
        <div class="rg-header">
            <div>
                <h1>📊 Reporte General</h1>
                <p>Análisis Estratégico • {{ $d['start_date'] }} — {{ $d['end_date'] }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="header-filters" style="min-width:240px;">{{ $this->form }}</div>
                <button wire:click="downloadPdf" class="bg-white/90 text-indigo-700 px-4 py-2 rounded-xl font-bold text-xs shadow hover:bg-white transition-all flex items-center gap-2 cursor-pointer">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4" /> Descargar PDF
                </button>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="rg-kpis">
            <div class="rg-kpi" style="--kc:#10b981">
                <div class="rg-kpi-lbl">Ventas Totales</div>
                <div class="rg-kpi-val">Q {{ number_format($d['totalSales'], 0) }}</div>
                <span class="rg-kpi-tag" style="background:#ecfdf5;color:#059669;">Q {{ number_format($d['totalPaid'], 0) }} Cobrado</span>
            </div>
            <div class="rg-kpi" style="--kc:#ef4444">
                <div class="rg-kpi-lbl">Costos Directos</div>
                <div class="rg-kpi-val">Q {{ number_format($d['totalCosts'], 0) }}</div>
                <span class="rg-kpi-tag" style="background:#fef2f2;color:#dc2626;">Base Manufactura</span>
            </div>
            <div class="rg-kpi" style="--kc:#3b82f6">
                <div class="rg-kpi-lbl">Utilidad Neta</div>
                <div class="rg-kpi-val">Q {{ number_format($d['earnings'], 0) }}</div>
                <span class="rg-kpi-tag" style="background:#eff6ff;color:#2563eb;">Margen: {{ number_format($d['margenBruto'], 1) }}%</span>
            </div>
            <div class="rg-kpi" style="--kc:#a855f7">
                <div class="rg-kpi-lbl">Ticket Promedio</div>
                <div class="rg-kpi-val">Q {{ number_format($d['ticketPromedio'], 0) }}</div>
                <span class="rg-kpi-tag" style="background:#faf5ff;color:#7e22ce;">Por venta</span>
            </div>
        </div>

        {{-- CHARTS ROW --}}
        <div class="rg-grid g-7-5" wire:ignore>
            <div class="rg-card" x-data="{
                chart: null,
                tryRender() {
                    if (typeof ApexCharts === 'undefined') { setTimeout(() => this.tryRender(), 200); return; }
                    if (this.chart) this.chart.destroy();
                    var isDark = document.documentElement.classList.contains('dark');
                    this.chart = new ApexCharts(this.$refs.trendChart, {
                        series: [
                            { name: 'Ventas (Q)', type: 'area', data: [{{ $d['dailySales']->pluck('total')->implode(',') }}] },
                            { name: 'Producción (Uds)', type: 'column', data: [{{ $d['dailyProduction']->pluck('total')->implode(',') }}] }
                        ],
                        chart: { height: 300, type: 'line', toolbar: {show:false}, fontFamily: 'Outfit, sans-serif', background: 'transparent' },
                        theme: { mode: isDark ? 'dark' : 'light' },
                        colors: ['#6366f1', '#10b981'],
                        stroke: { width: [3, 0], curve: 'smooth' },
                        fill: { opacity: [0.12, 0.85] },
                        grid: { borderColor: isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)' },
                        xaxis: { categories: [@foreach($d['dailySales'] as $day)'{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}',@endforeach], labels: { style: { fontWeight: 700 } } },
                        yaxis: [
                            { title: { text: 'Ingresos (Q)' }, labels: { formatter: v => 'Q ' + Math.round(v).toLocaleString() } },
                            { opposite: true, title: { text: 'Producción' }, labels: { formatter: v => Math.round(v).toLocaleString() } }
                        ],
                        tooltip: { shared: true, intersect: false, theme: 'dark' }
                    });
                    this.chart.render();
                }
            }" x-init="tryRender()">
                <div class="rg-card-title">Tendencia: Ventas vs Producción</div>
                <div class="rg-chart-scroll"><div x-ref="trendChart" style="min-width:550px;"></div></div>
            </div>

            <div class="rg-card" x-data="{
                chart: null,
                tryRender() {
                    if (typeof ApexCharts === 'undefined') { setTimeout(() => this.tryRender(), 200); return; }
                    if (this.chart) this.chart.destroy();
                    var isDark = document.documentElement.classList.contains('dark');
                    var seriesData = [{{ $d['profitByProduct']->pluck('profit')->implode(',') }}];
                    if (seriesData.length === 0 || seriesData.every(v => v === 0)) { this.$refs.donutChart.innerHTML = '<div style=\"text-align:center;padding:60px 20px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;\">Sin datos de utilidad en este período</div>'; return; }
                    this.chart = new ApexCharts(this.$refs.donutChart, {
                        series: seriesData,
                        chart: { type: 'donut', height: 300, fontFamily: 'Outfit, sans-serif' },
                        theme: { mode: isDark ? 'dark' : 'light' },
                        labels: [@foreach($d['profitByProduct'] as $p)'{{ $p->name }}',@endforeach],
                        colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                        legend: { position: 'bottom', fontWeight: 700 },
                        plotOptions: { donut: { size: '60%', labels: { show: true, name: { show: true }, value: { show: true, formatter: v => 'Q ' + Number(v).toLocaleString() }, total: { show: true, label: 'UTILIDAD', formatter: () => 'Q {{ number_format($d["earnings"], 0) }}' } } } },
                        dataLabels: { enabled: false }
                    });
                    this.chart.render();
                }
            }" x-init="tryRender()">
                <div class="rg-card-title">Distribución de Utilidad</div>
                <div x-ref="donutChart"></div>
            </div>
        </div>

        {{-- TOP PRODUCTS --}}
        <div class="rg-grid g-12" wire:ignore>
            <div class="rg-card" x-data="{
                chart: null,
                tryRender() {
                    if (typeof ApexCharts === 'undefined') { setTimeout(() => this.tryRender(), 200); return; }
                    if (this.chart) this.chart.destroy();
                    var isDark = document.documentElement.classList.contains('dark');
                    var cats = [@foreach($d['topProducts'] as $item)'{{ $item->name }}',@endforeach];
                    if (cats.length === 0) { this.$refs.topBar.innerHTML = '<div style=\"text-align:center;padding:40px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;\">Sin productos vendidos en este período</div>'; return; }
                    this.chart = new ApexCharts(this.$refs.topBar, {
                        series: [{ name: 'Utilidad', data: [{{ $d['topProducts']->pluck('profit')->implode(',') }}] }],
                        chart: { type: 'bar', height: 260, toolbar: {show:false}, fontFamily: 'Outfit, sans-serif' },
                        theme: { mode: isDark ? 'dark' : 'light' },
                        colors: ['#10b981', '#6366f1', '#f59e0b', '#ef4444', '#8b5cf6'],
                        plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '50%', distributed: true } },
                        xaxis: { categories: cats },
                        dataLabels: { enabled: true, formatter: v => 'Q ' + Math.round(v).toLocaleString(), style: { fontWeight: 900 } },
                        grid: { borderColor: isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)' },
                        legend: { show: false }
                    });
                    this.chart.render();
                }
            }" x-init="tryRender()">
                <div class="rg-card-title">Top 5 Productos más Rentables</div>
                <div x-ref="topBar"></div>
            </div>
        </div>

        {{-- AUDITORÍA: VENDEDORES Y PILOTOS --}}
        <div class="rg-grid g-6-6">
            {{-- Vendedores --}}
            <div class="rg-card">
                <div class="rg-card-title">Auditoría de Vendedores</div>
                @forelse($d['salesByUser'] as $row)
                    <div class="rg-person">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="rg-avatar" style="background:rgba(79,70,229,0.1);color:#6366f1;">{{ strtoupper(substr($row->name, 0, 1)) }}</div>
                            <div class="min-w-0">
                                <div class="rg-name truncate">{{ $row->name }}</div>
                                <div class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase">{{ $row->count }} ventas realizadas</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="text-right">
                                <div class="text-sm font-black text-indigo-600 dark:text-indigo-400">Q {{ number_format($row->total_sales, 0) }}</div>
                            </div>
                            <a href="{{ url('/admin/sales?tableFilters[status][value]=confirmed&tableFilters[sale_date][from]=' . $d['start_raw'] . '&tableFilters[sale_date][until]=' . $d['end_raw'] . '&tableFilters[created_by][value]=' . $row->id) }}"
                               class="rg-link rg-link-indigo">
                                VER <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3" />
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="rg-empty">No hay ventas en este período</div>
                @endforelse
            </div>

            {{-- Pilotos --}}
            <div class="rg-card">
                <div class="rg-card-title">Auditoría de Pilotos</div>
                @forelse($d['dispatchesByDriver'] as $row)
                    <div class="rg-person">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="rg-avatar" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                                <x-heroicon-o-truck class="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="rg-name truncate">{{ $row->driver_name }}</div>
                                <div class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase">{{ $row->count }} viajes completados</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="text-right">
                                <div class="text-sm font-black text-blue-600 dark:text-blue-400">{{ $row->count }} viajes</div>
                            </div>
                            @if($row->driver_id)
                                <a href="{{ url('/admin/dispatches?tableFilters[created_at][from]=' . $d['start_raw'] . '&tableFilters[created_at][until]=' . $d['end_raw'] . '&tableFilters[driver_id][value]=' . $row->driver_id) }}"
                                   class="rg-link rg-link-blue">
                                    AUDITAR <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3" />
                                </a>
                            @else
                                <span class="text-[9px] text-slate-400 italic font-bold">Sin ID</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rg-empty">No hay despachos en este período</div>
                @endforelse
            </div>
        </div>

        {{-- PRODUCCIÓN DETALLADA --}}
        @if($d['productionDetailed']->count() > 0)
        <div class="rg-grid g-12" style="margin-top:4px;">
            <div class="rg-card">
                <div class="rg-card-title">Detalle de Producción por Turno</div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr style="border-bottom:2px solid #e2e8f0;">
                                <th style="text-align:left;padding:8px 12px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b;">Turno</th>
                                <th style="text-align:left;padding:8px 12px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b;">Producto</th>
                                <th style="text-align:right;padding:8px 12px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b;">Cantidad</th>
                                <th style="text-align:right;padding:8px 12px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b;">Eficiencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($d['productionDetailed'] as $row)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:8px 12px;font-weight:700;color:#334155;" class="dark:text-slate-300">{{ $row->shift_name }}</td>
                                <td style="padding:8px 12px;font-weight:600;color:#64748b;" class="dark:text-slate-400">{{ $row->product_name }}</td>
                                <td style="padding:8px 12px;text-align:right;font-weight:800;color:#0f172a;" class="dark:text-white">{{ number_format($row->total_qty) }}</td>
                                <td style="padding:8px 12px;text-align:right;">
                                    @if($row->eficiencia !== null)
                                        <span style="font-weight:800;font-size:11px;padding:2px 8px;border-radius:6px;{{ $row->eficiencia >= 100 ? 'background:#ecfdf5;color:#059669;' : ($row->eficiencia >= 70 ? 'background:#fefce8;color:#ca8a04;' : 'background:#fef2f2;color:#dc2626;') }}">
                                            {{ number_format($row->eficiencia, 0) }}%
                                        </span>
                                    @else
                                        <span style="color:#94a3b8;font-size:10px;">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
