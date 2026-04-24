@php
$d   = $this->getChartData();
$uid = 'pshift-' . uniqid();
@endphp

<div x-data="{
    tab: 'comparison',
    init() {
        this.initCharts();
        document.addEventListener('livewire:navigated', () => {
            this.initCharts();
        }, { once: true });
    },
    switchTab(t) {
        this.tab = t;
        setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 50);
    },
    initCharts() {
        const uid = '{{ $uid }}';
        const palette = @json($d['palette']);
        const cmpEl = document.getElementById(uid + '-cmp');
        const trnEl = document.getElementById(uid + '-trn');

        if (typeof ApexCharts === 'undefined') {
            setTimeout(() => this.initCharts(), 300);
            return;
        }

        // Comparison Chart
        if (cmpEl && !cmpEl._chart) {
            const cmpChart = new ApexCharts(cmpEl, {
                chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                series: [{ name: 'Producción', data: @json($d['compValues']) }],
                xaxis: { categories: @json($d['compNames']), labels: { style: { fontWeight: '700' } } },
                colors: palette,
                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
                dataLabels: { enabled: true, style: { fontWeight: '900' }, formatter: function(v){ return Number(v).toLocaleString(); } },
                legend: { show: false },
                grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
                yaxis: { labels: { formatter: function(v){ return Number(v).toLocaleString(); } } }
            });
            cmpChart.render();
            cmpEl._chart = cmpChart;
        }

        // Trend Chart
        if (trnEl && !trnEl._chart) {
            const trnChart = new ApexCharts(trnEl, {
                chart: { type: 'line', height: 350, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                series: @json($d['trendSeries']),
                xaxis: { categories: @json($d['trendLabels']), labels: { style: { fontWeight: '600' } } },
                colors: palette,
                stroke: { curve: 'smooth', width: 3 },
                dataLabels: {
                    enabled: true,
                    style: { fontSize: '9px', fontWeight: '900' },
                    background: { enabled: true, foreColor: '#fff', padding: 3, borderRadius: 4, borderWidth: 0 },
                    formatter: function(v){ return Number(v).toLocaleString(); }
                },
                legend: { position: 'top', fontWeight: '700' },
                grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
                tooltip: { shared: true }
            });
            trnChart.render();
            trnEl._chart = trnChart;
        }
    }
}">
    {{-- Mode Tabs --}}
    <div style="display:flex;gap:8px;padding:12px 16px;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;">
        <button @click="switchTab('comparison')"
            :style="tab === 'comparison' ? 'background:var(--pb-accent, #4f46e5);color:#fff;' : 'background:var(--pb-subtle, #f1f5f9);color:var(--pb-muted, #64748b);'"
            style="font-size:11px;font-weight:700;padding:5px 14px;border-radius:100px;cursor:pointer;transition:all .15s;border:none;">
            Comparación por Turno
        </button>
        <button @click="switchTab('trend')"
            :style="tab === 'trend' ? 'background:var(--pb-accent, #4f46e5);color:#fff;' : 'background:var(--pb-subtle, #f1f5f9);color:var(--pb-muted, #64748b);'"
            style="font-size:11px;font-weight:700;padding:5px 14px;border-radius:100px;cursor:pointer;transition:all .15s;border:none;">
            Tendencia Temporal
        </button>
    </div>

    {{-- Chart panels --}}
    <div wire:ignore style="position:relative;">
        <div x-show="tab === 'comparison'" style="overflow-x:auto;">
            <div id="{{ $uid }}-cmp" style="width:100%;min-height:350px;"></div>
        </div>
        <div x-show="tab === 'trend'" style="overflow-x:auto;display:none;" x-bind:style="tab === 'trend' ? '' : 'display:none;'">
            <div id="{{ $uid }}-trn" style="width:100%;min-height:350px;"></div>
        </div>
    </div>

</div>
