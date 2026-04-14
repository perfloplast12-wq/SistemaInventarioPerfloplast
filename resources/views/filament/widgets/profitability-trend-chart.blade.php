@php $d = $this->getChartData(); @endphp
<div x-data="{
    chart: null,
    init() {
        this.render();
    },
    render() {
        if (this.chart) this.chart.destroy();
        this.chart = new ApexCharts(this.$refs.canvas, {
            chart: { type: 'area', height: 360, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
            series: [
                { name: 'Ingresos (Q)', data: @js($d['income']) },
                { name: 'Ganancia (Q)', data: @js($d['profit']) }
            ],
            xaxis: { categories: @js($d['labels']), labels: { style: { fontWeight: '600' } } },
            colors: ['#6366f1', '#10b981'],
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [20, 100] } },
            dataLabels: {
                enabled: true,
                style: { fontSize: '9px', fontWeight: '900' },
                background: { enabled: true, foreColor: '#fff', padding: 3, borderRadius: 4, borderWidth: 0 },
                formatter: function(v){ return 'Q '+Number(v).toLocaleString(); }
            },
            yaxis: { labels: { formatter: function(v){ return 'Q '+Number(v).toLocaleString(); } } },
            legend: { position: 'top', fontWeight: '700' },
            grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
            tooltip: { shared: true, y: { formatter: function(v){ return 'Q '+Number(v).toLocaleString(); } } }
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
        <div x-ref="canvas" wire:ignore style="min-height:360px;width:100%;min-width:800px;"></div>
    </div>
</div>
