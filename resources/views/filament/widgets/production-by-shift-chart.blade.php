@php
$d   = $this->getChartData();
$uid = 'pshift-' . uniqid();
@endphp

<div x-data="{
    tab: 'comparison',
    init() {
        this.render();
        document.addEventListener('livewire:navigated', () => this.render(), { once: true });
    },
    switchTab(t) {
        this.tab = t;
        setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 50);
    },
    render() {
        const uid = '{{ $uid }}';
        const palette = @json($d['palette']);
        
        if (typeof ApexCharts === 'undefined') {
            setTimeout(() => this.render(), 300);
            return;
        }

        // Comparison Chart
        const cmpEl = document.getElementById(uid + '-cmp');
        if (cmpEl && !cmpEl._chart) {
            cmpEl._chart = new ApexCharts(cmpEl, {
                chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                series: [{ name: 'Producción', data: @json($d['compValues']) }],
                xaxis: { categories: @json($d['compNames']), labels: { style: { fontWeight: '700' } } },
                colors: palette,
                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
                dataLabels: { enabled: true, style: { fontWeight: '900' }, formatter: v => Number(v).toLocaleString() },
                grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
                yaxis: { labels: { formatter: v => Number(v).toLocaleString() } }
            });
            cmpEl._chart.render();
        }

        // Trend Chart
        const trnEl = document.getElementById(uid + '-trn');
        if (trnEl && !trnEl._chart) {
            trnEl._chart = new ApexCharts(trnEl, {
                chart: { type: 'line', height: 350, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                series: @json($d['trendSeries']),
                xaxis: { categories: @json($d['trendLabels']), labels: { style: { fontWeight: '600' } } },
                colors: palette,
                stroke: { curve: 'smooth', width: 3 },
                dataLabels: {
                    enabled: true,
                    style: { fontSize: '9px', fontWeight: '900' },
                    background: { enabled: true, foreColor: '#fff', padding: 3, borderRadius: 4, borderWidth: 0 },
                    formatter: v => Number(v).toLocaleString()
                },
                legend: { position: 'top', fontWeight: '700' },
                grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
                tooltip: { shared: true }
            });
            trnEl._chart.render();
        }
    }
}">
    {{-- Mode Tabs --}}
    <div style="display:flex;gap:8px;padding:12px 16px;border-bottom:1px solid rgba(148, 163, 184, 0.1);flex-wrap:wrap;">
        <button @click="switchTab('comparison')"
            :style="tab === 'comparison' ? 'background:var(--p-1, #4f46e5);color:#fff;' : 'background:rgba(0,0,0,0.05);color:gray;'"
            style="font-size:11px;font-weight:700;padding:6px 14px;border-radius:100px;cursor:pointer;border:none;">
            Comparación por Turno
        </button>
        <button @click="switchTab('trend')"
            :style="tab === 'trend' ? 'background:var(--p-1, #4f46e5);color:#fff;' : 'background:rgba(0,0,0,0.05);color:gray;'"
            style="font-size:11px;font-weight:700;padding:6px 14px;border-radius:100px;cursor:pointer;border:none;">
            Tendencia Temporal
        </button>
    </div>

    {{-- Chart panels --}}
    <div wire:ignore style="position:relative; padding: 10px;">
        <div x-show="tab === 'comparison'">
            <div id="{{ $uid }}-cmp" style="width:100%;min-height:350px;"></div>
        </div>
        <div x-show="tab === 'trend'" x-cloak>
            <div id="{{ $uid }}-trn" style="width:100%;min-height:350px;"></div>
        </div>
    </div>
</div>
