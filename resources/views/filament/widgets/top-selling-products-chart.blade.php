@php $d = $this->getChartData(); @endphp
<div x-data="{
    chart: null,
    init() {
        this.render();
    },
    render() {
        // Check if profits data is empty
        const profits = @js($d['profits']);
        if (!profits || profits.length === 0) {
            this.$refs.canvas.innerHTML = '<div style=\'display:flex;align-items:center;justify-content:center;height:200px;color:#94a3b8;font-size:13px;font-family:Outfit,sans-serif;\'>Sin ventas en el período seleccionado</div>';
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
            return;
        }

        if (this.chart) this.chart.destroy();
        this.chart = new ApexCharts(this.$refs.canvas, {
            chart: { type: 'bar', height: 360, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
            series: [{ name: 'Ganancia (Q)', data: profits }],
            xaxis: { categories: @js($d['labels']), labels: { style: { fontWeight: '700', colors: '#64748b' } }, axisBorder: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 6, distributed: true, dataLabels: { position: 'center' }, barHeight: '55%' } },
            colors: ['#6366f1','#10b981','#f59e0b','#f43f5e','#8b5cf6'],
            legend: { show: false },
            dataLabels: {
                enabled: true,
                style: { fontSize: '11px', fontWeight: '800', colors: ['#ffffff'] },
                formatter: function(v){ return 'Q ' + Number(v).toLocaleString(); },
                dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.5 }
            },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
            yaxis: { labels: { maxWidth: 160, style: { fontWeight: '800', colors: '#475569' } } },
            tooltip: { theme: 'dark', y: { formatter: function(v){ return 'Q ' + Number(v).toLocaleString() + ' de ganancia'; } } }
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
        <div x-ref="canvas" wire:ignore style="min-height:300px;width:100%;min-width:800px;"></div>
    </div>
</div>
