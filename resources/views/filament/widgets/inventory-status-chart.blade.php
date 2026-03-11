@php $d = $this->getChartData(); @endphp
<div x-data="{
    chart: null,
    init() {
        this.render();
    },
    render() {
        if (this.chart) this.chart.destroy();
        this.chart = new ApexCharts(this.$refs.canvas, {
            chart: { type: 'donut', height: 360, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
            series: [@js($d['normal']), @js($d['low']), @js($d['critical'])],
            labels: ['Normal', 'Alerta (Bajo)', 'Crítico'],
            colors: ['#10b981', '#f59e0b', '#f43f5e'],
            legend: {
                position: 'bottom', fontWeight: '800', fontFamily: 'Outfit, sans-serif',
                markers: { radius: 12 },
                formatter: function(name, opts) {
                    return name + ' - ' + opts.w.globals.series[opts.seriesIndex] + ' Prod.';
                }
            },
            plotOptions: {
                pie: { donut: { size: '75%', labels: {
                    show: true,
                    name: { fontSize: '13px', fontWeight: '800', color: '#64748b' },
                    value: { fontWeight: '900', fontSize: '32px', color: '#1e293b', formatter: function(v){ return v; } },
                    total: { show: true, label: 'Productos', fontWeight: '800', color: '#64748b', fontSize: '14px',
                        formatter: function(w){ return w.globals.seriesTotals.reduce(function(a,b){ return a+b; }, 0); } }
                }}}
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '12px', fontWeight: '900', fontFamily: 'Outfit, sans-serif' },
                background: { enabled: true, foreColor: '#1e293b', padding: 4, borderRadius: 4, borderWidth: 1, borderColor: '#e2e8f0', opacity: 0.95 },
                dropShadow: { enabled: false },
                formatter: function(val, opts) {
                    var count = opts.w.globals.series[opts.seriesIndex];
                    return Math.round(val) + '% (' + count + ')';
                }
            },
            stroke: { width: 3, colors: ['#ffffff'] },
            tooltip: { theme: 'dark', y: { formatter: function(v){ return v + ' productos en este estado'; } } }
        });
        this.chart.render();
    }
}">
    {{-- ── FILTRO LOCAL ── --}}
    <div class="flex items-center justify-end gap-2 mb-4">
        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Filtrar por Bodega:</label>
        <select wire:model.live="local_warehouse_id" class="text-xs font-bold h-8 rounded-lg border-gray-200 bg-white px-3 py-1 text-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all focus:outline-none">
            <option value="">Todas las bodegas</option>
            @foreach($d['warehouses'] as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="pb-chart-scrollable">
        <div x-ref="canvas" wire:ignore style="min-height:360px;width:100%;min-width:600px;"></div>
    </div>
</div>
