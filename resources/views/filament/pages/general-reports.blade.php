<x-filament-panels::page>
    @php
        $d = $this->getReportData();
    @endphp

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
        .strategic-dashboard { font-family: 'Outfit', sans-serif; margin: -24px; padding: 20px; min-height: 100vh; }
        
        /* Header Responsive */
        .strategic-header { 
            background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%); 
            border-radius: 16px; 
            padding: 24px; 
            color: white; 
            margin-bottom: 24px; 
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.3);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        .header-content h1 { font-size: 24px; font-weight: 900; margin: 0; }
        .header-content p { font-size: 13px; opacity: 0.8; margin-top: 4px; }
        
        /* KPIs Row - 2 cols on mobile, 4 on desktop */
        .kpi-row { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 16px; 
            margin-bottom: 24px; 
        }
        @media (min-width: 1024px) {
            .kpi-row { grid-template-columns: repeat(4, 1fr); gap: 24px; }
        }

        .kpi-card { 
            background: white; 
            padding: 20px; 
            border-radius: 16px; 
            border: 1px solid #f1f5f9; 
            border-left: 5px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 140px;
        }
        .dark .kpi-card { background: #1e293b; border-color: #334155; }
        
        .kpi-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-val { font-size: 22px; font-weight: 900; color: #0f172a; margin: 8px 0; }
        .dark .kpi-val { color: #f8fafc; }
        .kpi-sub { font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 6px; }
        
        .c-emerald { border-left-color: #10b981; } .s-emerald { background: #ecfdf5; color: #059669; }
        .c-red { border-left-color: #ef4444; } .s-red { background: #fef2f2; color: #dc2626; }
        .c-blue { border-left-color: #3b82f6; } .s-blue { background: #eff6ff; color: #2563eb; }
        .c-purple { border-left-color: #a855f7; } .s-purple { background: #faf5ff; color: #7e22ce; }

        /* Sections Grid */
        .grid-responsive { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 24px; }
        @media (min-width: 1024px) {
            .grid-responsive.split-7-5 { grid-template-columns: 7fr 5fr; }
            .grid-responsive.split-6-6 { grid-template-columns: 1fr 1fr; }
        }

        .sec-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #f1f5f9; }
        .dark .sec-card { background: #1e293b; border-color: #334155; }
        .sec-title { font-size: 11px; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; }
        .dark .sec-title { border-bottom-color: #334155; }

        /* List Items */
        .list-item { display: flex; align-items: center; justify-content: space-between; padding: 12px; border: 1px solid #f8fafc; border-radius: 10px; margin-bottom: 8px; }
        .dark .list-item { background: #0f172a; border-color: #334155; }
        .item-avatar { width: 30px; height: 30px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px; }
        .dark .item-avatar { background: #334155; color: #f8fafc; }
        .item-name { font-size: 12px; font-weight: 700; color: #334155; }
        .dark .item-name { color: #f8fafc; }

        /* Scrollable Charts */
        .chart-scroll { overflow-x: auto; scrollbar-width: none; }
        .chart-scroll::-webkit-scrollbar { display: none; }
    </style>

    <div class="strategic-dashboard">
        <div class="strategic-header">
            <div class="header-content">
                <h1>Reporte General</h1>
                <p>Análisis Estratégico Consolidado • Perflo-Plast</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="header-filters" style="min-width: 250px;">{{ $this->form }}</div>
                <button wire:click="downloadPdf" class="bg-white text-indigo-600 px-4 py-2 rounded-xl font-bold text-sm shadow-lg hover:bg-slate-50 transition-all flex items-center gap-2">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4" /> PDF
                </button>
            </div>
        </div>

        <div class="kpi-row">
            <div class="kpi-card c-emerald">
                <span class="kpi-label">Ventas Totales</span>
                <div class="kpi-val">Q {{ number_format($d['totalSales'], 0) }}</div>
                <span class="kpi-sub s-emerald">Q {{ number_format($d['totalPaid'], 0) }} Cobrado</span>
            </div>
            <div class="kpi-card c-red">
                <span class="kpi-label">Costos Est.</span>
                <div class="kpi-val">Q {{ number_format($d['totalCosts'], 0) }}</div>
                <span class="kpi-sub s-red">Costo Manufactura</span>
            </div>
            <div class="kpi-card c-blue">
                <span class="kpi-label">Utilidad</span>
                <div class="kpi-val">Q {{ number_format($d['earnings'], 0) }}</div>
                <span class="kpi-sub s-blue">Margen: {{ number_format($d['margenBruto'], 1) }}%</span>
            </div>
            <div class="kpi-card c-purple">
                <span class="kpi-label">Ticket Prom.</span>
                <div class="kpi-val">Q {{ number_format($d['ticketPromedio'], 0) }}</div>
                <span class="kpi-sub s-purple">Por cada venta</span>
            </div>
        </div>

        <div class="grid-responsive split-7-5" wire:ignore>
            <div class="sec-card" x-data="{
                chart: null,
                render() {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(this.$refs.mainChart, {
                        series: [
                            { name: 'Ventas', type: 'area', data: @json($d['dailySales']->pluck('total')) },
                            { name: 'Producción', type: 'column', data: @json($d['dailyProduction']->pluck('total')) }
                        ],
                        chart: { height: 300, type: 'line', toolbar: {show:false}, fontFamily: 'Outfit', background: 'transparent' },
                        theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                        colors: ['#4f46e5', '#10b981'],
                        stroke: { width: [3, 0], curve: 'smooth' },
                        fill: { opacity: [0.1, 1] },
                        grid: { borderColor: 'rgba(148, 163, 184, 0.1)' },
                        xaxis: { categories: @json($d['dailySales']->map(fn($v) => Carbon\Carbon::parse($v->date)->format('d/m'))) }
                    });
                    this.chart.render();
                }
            }" x-init="setTimeout(() => render(), 300)">
                <div class="sec-title">Tendencia: Ventas vs Producción</div>
                <div class="chart-scroll"><div x-ref="mainChart" style="min-width: 600px;"></div></div>
            </div>

            <div class="sec-card" x-data="{
                chart: null,
                render() {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(this.$refs.donutChart, {
                        series: @json($d['profitByProduct']->pluck('profit')),
                        chart: { type: 'donut', height: 300, fontFamily: 'Outfit' },
                        theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                        labels: @json($d['profitByProduct']->pluck('name')),
                        colors: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                        legend: { position: 'bottom' },
                        dataLabels: { enabled: false }
                    });
                    this.chart.render();
                }
            }" x-init="setTimeout(() => render(), 300)">
                <div class="sec-title">Utilidad por Producto</div>
                <div x-ref="donutChart"></div>
            </div>
        </div>

        <div class="grid-responsive split-6-6">
            <div class="sec-card">
                <div class="sec-title">Auditoría Vendedores</div>
                @foreach($d['salesByUser'] as $row)
                    <div class="list-item">
                        <div class="flex items-center gap-3">
                            <div class="item-avatar">{{ substr($row->name, 0, 1) }}</div>
                            <div class="item-name">{{ $row->name }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-black text-indigo-600 dark:text-indigo-400">Q {{ number_format($row->total_sales, 0) }}</div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase">{{ $row->count }} ventas</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sec-card">
                <div class="sec-title">Auditoría Pilotos</div>
                @foreach($d['dispatchesByDriver'] as $row)
                    <div class="list-item">
                        <div class="flex items-center gap-3">
                            <div class="item-avatar bg-blue-50 text-blue-600"><x-heroicon-o-truck class="w-4 h-4" /></div>
                            <div class="item-name">{{ $row->driver_name }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-black text-blue-600 dark:text-blue-400">{{ $row->count }} viajes</div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase">Logística</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
