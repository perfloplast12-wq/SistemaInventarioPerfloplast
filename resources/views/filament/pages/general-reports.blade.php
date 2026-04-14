<x-filament-panels::page>
    @php
        $d = $this->getReportData();
    @endphp

    <!-- Cargador Dinámico de ApexCharts robusto para Livewire -->
    <div x-data="{
        init() {
            if (!window.ApexCharts && !document.getElementById('apexcharts-script')) {
                const script = document.createElement('script');
                script.id = 'apexcharts-script';
                script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                script.onload = () => window.dispatchEvent(new CustomEvent('apexcharts-loaded'));
                document.head.appendChild(script);
            }
        }
    }"></div>

    <style>
        .strategic-dashboard { font-family: 'Outfit', 'Inter', sans-serif; background: #fdfdfd; margin: -24px; padding: 24px; min-height: 100vh; color: #1e293b; }
        
        /* Strategic Purple Header */
        .strategic-header { 
            background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%); 
            border-radius: 20px; 
            padding: 30px 40px; 
            color: white; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-content h1 { font-size: 28px; font-weight: 900; margin: 0; letter-spacing: -0.02em; }
        .header-content p { font-size: 14px; opacity: 0.8; font-weight: 500; margin: 5px 0 0; }
        
        /* Filters inside Header */
        .header-filters { display: flex; align-items: center; gap: 15px; }
        .header-filters :deep(.fi-fo-field-wrp-label) { display: none; }
        .header-filters :deep(.fi-input-wrp) { background: rgba(255,255,255,0.1) !important; border: 1px solid rgba(255,255,255,0.2) !important; border-radius: 12px !important; }
        .header-filters :deep(input) { color: white !important; font-weight: 600 !important; }
        .header-filters :deep(input::placeholder) { color: rgba(255,255,255,0.5) !important; }
        
        .header-actions { display: flex; gap: 12px; }
        .btn-action { background: rgba(255,255,255,0.15); color: white; padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px; border: 1px solid rgba(255,255,255,0.25); text-decoration: none; transition: all 0.2s; cursor: pointer; }
        .btn-action:hover { background: rgba(255,255,255,0.25); transform: translateY(-2px); }
        .btn-pdf { background: white; color: #4f46e5; border: none; }
        .btn-pdf:hover { background: #f1f5f9; color: #312e81; }

        /* KPI Vertical Cards */
        .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 30px; }
        .kpi-card { 
            background: white; 
            padding: 24px; 
            border-radius: 18px; 
            border: 1px solid #f1f5f9; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            border-left: 6px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
            transition: transform 0.2s;
        }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .kpi-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .kpi-label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-icon { color: #94a3b8; }
        .kpi-val { font-size: 32px; font-weight: 900; color: #0f172a; margin: 10px 0; }
        .kpi-sub { 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            padding: 6px 12px; 
            border-radius: 8px; 
            font-size: 11px; 
            font-weight: 800; 
            text-transform: uppercase; 
        }
        
        .c-emerald { border-left-color: #10b981; } .s-emerald { background: #ecfdf5; color: #059669; }
        .c-red { border-left-color: #ef4444; } .s-red { background: #fef2f2; color: #dc2626; }
        .c-blue { border-left-color: #3b82f6; } .s-blue { background: #eff6ff; color: #2563eb; }
        .c-purple { border-left-color: #a855f7; } .s-purple { background: #faf5ff; color: #7e22ce; }

        /* Sections and Content */
        .sec-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .sec-title { font-size: 12px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .sec-title::after { content: ''; flex: 1; height: 1px; background: #f1f5f9; }

        /* Direct Links Lists */
        .list-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; border: 1px solid #f8fafc; border-radius: 12px; margin-bottom: 10px; background: #fff; transition: border-color 0.2s; }
        .list-item:hover { border-color: #e2e8f0; }
        .item-info { display: flex; align-items: center; gap: 12px; }
        .item-avatar { width: 34px; height: 34px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: 900; color: #475569; font-size: 12px; }
        .item-name { font-size: 13px; font-weight: 800; color: #334155; text-transform: uppercase; }
        .item-stats { text-align: right; display: flex; align-items: center; gap: 15px; }
        .item-val { font-size: 14px; font-weight: 900; color: #0f172a; }
        
        .link-ver { font-size: 11px; font-weight: 900; text-transform: uppercase; text-decoration: none; padding: 6px 12px; border-radius: 8px; transition: all 0.2s; }
        .link-sales { color: #4f46e5; background: rgba(79, 70, 229, 0.1); }
        .link-sales:hover { background: rgba(79, 70, 229, 0.2); }
        .link-dispatch { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
        .link-dispatch:hover { background: rgba(59, 130, 246, 0.2); }

        .grid-12 { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; }
        .col-8 { grid-column: span 8; }
        .col-4 { grid-column: span 4; }
        .col-5 { grid-column: span 5; }
        .col-7 { grid-column: span 7; }
        .col-6 { grid-column: span 6; }
        .col-12 { grid-column: span 12; }

        /* --- Modo Oscuro (Filament Support) --- */
        .dark .strategic-dashboard { background-color: transparent; color: #f8fafc; }
        .dark .kpi-card, .dark .sec-card { background-color: #1e293b; border-color: #334155; box-shadow: none; }
        .dark .kpi-label, .dark .sec-title { color: #cbd5e1; }
        .dark .sec-title::after { background: #334155; }
        .dark .kpi-val, .dark .item-val, .dark .item-name { color: #f8fafc; }
        
        .dark .s-emerald { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .dark .s-red { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .dark .s-blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .dark .s-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }

        .dark .list-item { background: #0f172a; border-color: #334155; }
        .dark .list-item:hover { border-color: #475569; }
        .dark .item-avatar { background: #334155; color: #f8fafc; }
        .dark .text-slate-400 { color: #94a3b8 !important; }
        
        /* ApexCharts Dark Mode Overrides */
        .dark .apexcharts-text { fill: #cbd5e1 !important; }
        .dark .apexcharts-legend-text { color: #cbd5e1 !important; }
        .dark .apexcharts-tooltip { background: #1e293b !important; border-color: #334155 !important; box-shadow: none !important; }
        .dark .apexcharts-tooltip-title { background: #0f172a !important; border-bottom: 1px solid #334155 !important; }
        .dark .apexcharts-tooltip-text { color: #f8fafc !important; }
        .dark .apexcharts-gridline { stroke: #334155 !important; }
    </style>

    <div class="strategic-dashboard">
        <!-- Purple Strategic Header -->
        <div class="strategic-header">
            <div class="header-content">
                <h1>Panel Gerencial y Financiero</h1>
                <p>Perflo-Plast • Módulo Estratégico de Análisis</p>
            </div>
            
            <div class="flex flex-col items-end gap-4">
                <div class="header-filters">
                    {{ $this->form }}
                </div>
                <div class="header-actions">
                    <button wire:click="downloadPdf" class="btn-action btn-pdf shadow-md">
                        <x-heroicon-o-document-text class="w-4 h-4" /> REPORTE FÍSICO PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Vertical Row (Like Second Photo) -->
        <div class="kpi-row">
            <div class="kpi-card c-emerald">
                <div class="kpi-top">
                    <span class="kpi-label">Ingresos Brutos</span>
                    <x-heroicon-o-banknotes class="w-5 h-5 kpi-icon" />
                </div>
                <div class="kpi-val">Q {{ number_format($d['totalSales'], 2) }}</div>
                <div>
                   <span class="kpi-sub s-emerald">
                       <x-heroicon-s-check-circle class="w-3 h-3" />
                       Q {{ number_format($d['totalPaid'], 0) }} Cobrado
                   </span>
                </div>
            </div>
            
            <div class="kpi-card c-red">
                <div class="kpi-top">
                    <span class="kpi-label">Costos Directos Est.</span>
                    <x-heroicon-o-swatch class="w-5 h-5 kpi-icon" />
                </div>
                <div class="kpi-val">Q {{ number_format($d['totalCosts'], 2) }}</div>
                <div>
                   <span class="kpi-sub s-red">
                       <x-heroicon-s-exclamation-triangle class="w-3 h-3" />
                       Base de Manufactura
                   </span>
                </div>
            </div>

            <div class="kpi-card c-blue">
                <div class="kpi-top">
                    <span class="kpi-label">Utilidad Neta Total</span>
                    <x-heroicon-o-briefcase class="w-5 h-5 kpi-icon" />
                </div>
                <div class="kpi-val">Q {{ number_format($d['earnings'], 2) }}</div>
                <div>
                   <span class="kpi-sub s-blue">
                       Margen Bruto: {{ number_format($d['margenBruto'], 1) }}%
                   </span>
                </div>
            </div>

            <div class="kpi-card c-purple">
                <div class="kpi-top">
                    <span class="kpi-label">Ticket Promedio / Venta</span>
                    <x-heroicon-o-credit-card class="w-5 h-5 kpi-icon" />
                </div>
                <div class="kpi-val">Q {{ number_format($d['ticketPromedio'], 2) }}</div>
                <div>
                   <span class="kpi-sub s-purple">
                       <x-heroicon-s-arrow-trending-up class="w-3 h-3" />
                       Rendimiento de Cartera
                   </span>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: Graphical Distribution -->
        <div class="grid-12 mb-6" wire:ignore>
            <!-- Distribución Utilidad (Left) -->
            <div class="col-5 sec-card" x-data="{
                chart: null,
                init() {
                    if (window.ApexCharts) { this.renderChart(); } 
                    else { window.addEventListener('apexcharts-loaded', () => this.renderChart()); }
                },
                renderChart() {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(this.$refs.donutChart, {
                        series: [ @foreach($d['profitByProduct'] as $p) {{ $p->profit }}, @endforeach ],
                        chart: { type: 'donut', height: 320, fontFamily: 'Outfit, sans-serif' },
                        labels: [ @foreach($d['profitByProduct'] as $p) '{{ $p->name }}', @endforeach ],
                        colors: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                        legend: { position: 'bottom', fontWeight: 700 },
                        plotOptions: { donut: { size: '65%', labels: { show: true, name: {show:true}, value: {show:true, formatter: v=>'Q'+Number(v).toLocaleString()}, total: { show: true, label: 'UTILIDAD TOTAL', formatter: () => 'Q {{ number_format($d['earnings'], 0) }}' } } } },
                        dataLabels: { enabled: false }
                    });
                    this.chart.render();
                }
            }">
                <div class="sec-title">Distribución de Utilidad por Producto</div>
                <div x-ref="donutChart"></div>
            </div>

            <!-- Top Productos (Right) -->
            <div class="col-7 sec-card" x-data="{
                chart: null,
                init() {
                    if (window.ApexCharts) { this.renderChart(); } 
                    else { window.addEventListener('apexcharts-loaded', () => this.renderChart()); }
                },
                renderChart() {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(this.$refs.barChart, {
                        series: [{
                            name: 'Utilidad Generada',
                            data: [ @foreach($d['topProducts'] as $item) {{ $item->profit }}, @endforeach ]
                        }],
                        chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                        colors: ['#10b981'],
                        plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '40%', distributed: true } },
                        xaxis: { categories: [ @foreach($d['topProducts'] as $item) '{{ $item->name }}', @endforeach ], labels: { style: { fontWeight: 700 } } },
                        dataLabels: { enabled: true, formatter: (v) => 'Q ' + Math.round(v).toLocaleString(), style: { fontWeight: 900, colors: ['#fff'] } },
                        grid: { borderColor: '#f1f5f9' },
                        legend: { show: false }
                    });
                    this.chart.render();
                }
            }">
                <div class="sec-title">Top 5 Productos Rentables</div>
                <div x-ref="barChart"></div>
            </div>
        </div>

        <!-- Row 2: Sales & Production Trends -->
        <div class="grid-12 mb-6" wire:ignore>
            <div class="col-12 sec-card" x-data="{
                chart: null,
                init() {
                    if (window.ApexCharts) { this.renderChart(); } 
                    else { window.addEventListener('apexcharts-loaded', () => this.renderChart()); }
                },
                renderChart() {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(this.$refs.multiChart, {
                        series: [
                            { name: 'Ventas (Cobranza)', type: 'area', data: [ @foreach($d['dailySales'] as $day) {{ $day->total }}, @endforeach ] },
                            { name: 'Producción (Uds)', type: 'column', data: [ @foreach($d['dailyProduction'] as $p) {{ $p->total }}, @endforeach ] }
                        ],
                        chart: { height: 350, type: 'line', toolbar: { show: false }, stacked: false, fontFamily: 'Outfit, sans-serif' },
                        stroke: { width: [4, 0], curve: 'smooth' },
                        colors: ['#4f46e5', '#34d399'],
                        fill: { opacity: [0.15, 0.9], gradient: { shade: 'light', type: 'vertical', opacityFrom: 0.5, opacityTo: 0.1 } },
                        xaxis: { categories: [ @foreach($d['dailySales'] as $day) '{{ Carbon\Carbon::parse($day->date)->format('d/m') }}', @endforeach ] },
                        yaxis: [
                            { title: { text: 'Ingresos (Q)' }, labels: { formatter: (v) => 'Q ' + Math.round(v).toLocaleString() } },
                            { opposite: true, title: { text: 'Volumen Producción' } }
                        ],
                        grid: { borderColor: '#f1f5f9' },
                        tooltip: { shared: true, intersect: false, theme: 'light' }
                    });
                    this.chart.render();
                }
            }">
                <div class="sec-title">Tendencia Combinada: Ventas vs Manufactura</div>
                <div class="pb-chart-scrollable">
                    <div x-ref="multiChart" style="min-width: 800px;"></div>
                </div>
            </div>
        </div>

        <!-- Row 3: People Management (Auditoría en Línea) -->
        <div class="grid-12">
            <!-- Vendedores -->
            <div class="col-6 sec-card">
                <div class="sec-title">Desempeño Vendedores (Auditoría)</div>
                <div class="space-y-3">
                    @foreach($d['salesByUser'] as $row)
                    <div class="list-item shadow-sm">
                        <div class="item-info">
                            <div class="item-avatar">{{ substr($row->name, 0, 1) }}</div>
                            <div class="item-name">{{ $row->name }}</div>
                        </div>
                        <div class="flex items-center">
                            <span class="item-val">Q {{ number_format($row->total_sales, 0) }}</span>
                            <a href="{{ url('/admin/sales?tableFilters[status][value]=confirmed&tableFilters[sale_date][from]=' . $d['start_raw'] . '&tableFilters[sale_date][until]=' . $d['end_raw'] . '&tableFilters[created_by][value]=' . $row->id) }}" class="btn-toggle text-indigo-600 border border-indigo-200 px-2 py-1 rounded-md ml-3 hover:bg-indigo-50 text-[11px] font-bold" style="text-decoration:none;">{{ $row->count }} V. &middot; VER</a>
                        </div>
                    </div>
                    @endforeach
                    @if(count($d['salesByUser']) == 0)
                        <div class="text-xs text-center text-slate-400 font-bold py-4">No hay ventas en este período.</div>
                    @endif
                </div>
            </div>

            <!-- Pilotos -->
            <div class="col-6 sec-card">
                <div class="sec-title">Rendimiento Logístico (Pilotos)</div>
                <div class="space-y-3">
                    @foreach($d['dispatchesByDriver'] as $row)
                    <div class="list-item shadow-sm" style="border-left: 4px solid #3b82f6;">
                        <div class="item-info">
                            <x-heroicon-o-truck class="w-5 h-5 text-blue-400" />
                            <div class="item-name truncate max-w-[150px]">{{ $row->driver_name }}</div>
                        </div>
                        <div class="flex items-center">
                            <span class="item-val text-blue-900">{{ $row->count }} Viajes</span>
                            @if($row->driver_id)
                                <a href="{{ url('/admin/dispatches?tableFilters[created_at][from]=' . $d['start_raw'] . '&tableFilters[created_at][until]=' . $d['end_raw'] . '&tableFilters[driver_id][value]=' . $row->driver_id) }}" class="btn-toggle text-blue-600 border border-blue-200 px-2 py-1 rounded-md ml-3 hover:bg-blue-50 text-[11px] font-bold" style="text-decoration:none;">AUDITAR</a>
                            @else
                                <span class="text-slate-400 text-[10px] ml-3 italic">Búsqueda Manual</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @if(count($d['dispatchesByDriver']) == 0)
                        <div class="text-xs text-center text-slate-400 font-bold py-4">No hay despachos en este período.</div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
