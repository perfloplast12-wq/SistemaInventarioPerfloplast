@php
$d   = $this->getChartData();
$uid = 'pshift-' . uniqid();
@endphp

<div x-data="{
    tab: 'comparison',
    switchTab(t) {
        this.tab = t;
        setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 50);
    }
}">
    {{-- Mode Tabs --}}
    <div style="display:flex;gap:8px;padding:12px 16px;border-bottom:1px solid #e2e8f0;">
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
        <div x-show="tab === 'comparison'" class="pb-chart-scrollable">
            <div id="{{ $uid }}-cmp" style="width:100%;min-height:400px;min-width:800px;"></div>
        </div>
        <div x-show="tab === 'trend'" class="pb-chart-scrollable" style="display:none;" x-bind:style="tab === 'trend' ? '' : 'display:none;'">
            <div id="{{ $uid }}-trn" style="width:100%;min-height:400px;min-width:800px;"></div>
        </div>
    </div>

    @script
    <script>
    (function(){
        var palette = @json($d['palette']);

        // Comparison
        (function(){
            var el = document.getElementById('{{ $uid }}-cmp');
            if (!el) return;
            function init() {
                if (typeof ApexCharts === 'undefined') { setTimeout(init, 200); return; }
                new ApexCharts(el, {
                    chart: { type: 'bar', height: 400, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                    series: [{ name: 'Producción', data: @json($d['compValues']) }],
                    xaxis: { categories: @json($d['compNames']), labels: { style: { fontWeight: '700' } } },
                    colors: palette,
                    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
                    dataLabels: { enabled: true, style: { fontWeight: '900' }, formatter: function(v){ return Number(v).toLocaleString(); } },
                    legend: { show: false },
                    grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
                    yaxis: { labels: { formatter: function(v){ return Number(v).toLocaleString(); } } }
                }).render();
            }
            if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
        })();

        // Trend
        (function(){
            var el = document.getElementById('{{ $uid }}-trn');
            if (!el) return;
            function init() {
                if (typeof ApexCharts === 'undefined') { setTimeout(init, 200); return; }
                new ApexCharts(el, {
                    chart: { type: 'line', height: 400, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                    series: @json($d['trendSeries']),
                    xaxis: { categories: @json($d['trendLabels']), labels: { style: { fontWeight: '600' } } },
                    colors: palette,
                    stroke: { curve: 'smooth', width: 4 },
                    dataLabels: {
                        enabled: true,
                        style: { fontSize: '9px', fontWeight: '900' },
                        background: { enabled: true, foreColor: '#fff', padding: 3, borderRadius: 4, borderWidth: 0 },
                        formatter: function(v){ return Number(v).toLocaleString(); }
                    },
                    legend: { position: 'top', fontWeight: '700' },
                    grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
                    tooltip: { shared: true }
                }).render();
            }
            if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
        })();

        })();
    })();
    </script>
    @endscript
</div>
