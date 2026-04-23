@php $d = $this->getChartData(); @endphp
<div x-data="{
    chart: null,
    init() {
        this.render();
    },
    render() {
        if (this.chart) this.chart.destroy();
        var isDark = document.documentElement.classList.contains('dark');
        var gc = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.05)';
        this.chart = new ApexCharts(this.$refs.canvas, {
            chart: { type: 'bar', height: 360, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif', stacked: false },
            series: [
                { name: 'Ingresos (Q)', data: @js($d['income']) },
                { name: 'Ganancia (Q)', data: @js($d['profit']) }
            ],
            xaxis: {
                categories: @js($d['labels']),
                labels: { style: { fontWeight: '700', fontSize: '10px', colors: isDark ? '#94a3b8' : '#64748b' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            colors: ['#6366f1', '#10b981'],
            plotOptions: {
                bar: { borderRadius: 6, columnWidth: '55%', dataLabels: { position: 'top' } }
            },
            dataLabels: {
                enabled: true,
                offsetY: -18,
                style: { fontSize: '9px', fontWeight: '900', colors: [isDark ? '#e2e8f0' : '#334155'] },
                formatter: function(v){ if(v === 0) return ''; return 'Q ' + Number(v).toLocaleString(); }
            },
            yaxis: {
                labels: {
                    style: { fontWeight: '700', colors: isDark ? '#64748b' : '#94a3b8' },
                    formatter: function(v){ return 'Q ' + Number(v).toLocaleString(); }
                }
            },
            legend: { position: 'top', fontWeight: '700', labels: { colors: isDark ? '#cbd5e1' : '#475569' } },
            grid: { borderColor: gc, strokeDashArray: 0 },
            tooltip: { theme: 'dark', shared: true, intersect: false, y: { formatter: function(v){ return 'Q ' + Number(v).toLocaleString(); } } }
        });
        this.chart.render();
    }
}">
    {{-- FILTRO LOCAL --}}
    <div class="flex items-center justify-end gap-2 mb-4">
        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Filtrar por Bodega:</label>
        <select wire:model.live="local_warehouse_id" class="text-xs font-bold h-8 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 bg-white px-3 py-1 text-gray-600 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all focus:outline-none">
            <option value="">Todas las bodegas</option>
            @foreach($d['warehouses'] as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="pb-chart-scrollable">
        <div x-ref="canvas" wire:ignore style="min-height:360px;width:100%;min-width:700px;"></div>
    </div>
</div>
