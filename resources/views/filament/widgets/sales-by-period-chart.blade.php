@php $d = $this->getChartData(); @endphp
<div x-data="{
    chart: null,
    init() {
        this.render();
    },
    render() {
        if (this.chart) this.chart.destroy();
        this.chart = new ApexCharts(this.$refs.canvas, {
            chart: { type: 'line', height: 380, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'Outfit, sans-serif' },
            series: [
                { name: 'Ingresos', type: 'column', data: @js($d['salesData']) },
                { name: 'Tasa Producción', type: 'line', data: @js($d['prodData']) }
            ],
            labels: @js($d['labels']),
            stroke: { width: [0, 4], curve: 'smooth' },
            colors: ['#6366f1', '#10b981'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
            dataLabels: {
                enabled: true,
                enabledOnSeries: [0, 1],
                style: { fontSize: '11px', fontWeight: '800', fontFamily: 'Outfit, sans-serif' },
                background: { enabled: true, foreColor: '#1e293b', padding: 4, borderRadius: 4, borderWidth: 1, borderColor: '#e2e8f0', opacity: 0.9 },
                formatter: function(val, opts) {
                    if(val == 0) return '';
                    return opts.seriesIndex === 0 ? 'Q ' + Number(val).toLocaleString() : Number(val).toLocaleString() + ' u';
                }
            },
            xaxis: { type: 'category', labels: { style: { fontWeight: '700', colors: '#64748b' } },
                axisBorder: { show: false }, axisTicks: { show: false }
            },
            yaxis: [
                { title: { text: 'Ingresos (Q)', style: { fontWeight: '800', color: '#6366f1' } }, labels: { style: { colors: '#6366f1', fontWeight: 'bold' }, formatter: function(v){ return 'Q ' + Number(v).toLocaleString(); } } },
                { opposite: true, title: { text: 'Unidades Producidas', style: { fontWeight: '800', color: '#10b981' } }, labels: { style: { colors: '#10b981', fontWeight: 'bold' }, formatter: function(v){ return Number(v).toLocaleString(); } } }
            ],
            legend: { position: 'top', horizontalAlign: 'right', fontWeight: '800', markers: { radius: 12 } },
            grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: true } } },
            tooltip: { 
                shared: true, intersect: false, 
                theme: 'dark',
                style: { fontSize: '12px', fontFamily: 'Outfit, sans-serif' },
                y: [
                    { formatter: function(v){ return 'Q ' + Number(v).toLocaleString(); } },
                    { formatter: function(v){ return Number(v).toLocaleString() + ' Unid'; } }
                ]
            }
        });
        this.chart.render();
    }
}">
    {{-- ── FILTRO LOCAL ── --}}
    <div class="flex items-center justify-end gap-2 mb-4">
        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Filtrar por Bodega:</label>
        <select wire:model.live="warehouse_id" class="text-xs font-bold h-8 rounded-lg border-gray-200 bg-white px-3 py-1 text-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all focus:outline-none">
            <option value="">Todas las bodegas</option>
            @foreach($d['warehouses'] as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="pb-chart-scrollable">
        <div x-ref="canvas" wire:ignore style="min-height:380px;width:100%;min-width:800px;"></div>
    </div>
</div>
