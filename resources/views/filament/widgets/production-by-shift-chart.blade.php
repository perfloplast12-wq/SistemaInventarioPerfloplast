@php
$d   = $this->getChartData();
$uid = 'pshift-' . uniqid();
@endphp

<div x-data="{ 
    tab: 'comparison',
    uid: '{{ $uid }}'
}" x-init="$dispatch('init-production-chart', { uid: uid, data: @js($d) })">
    {{-- Mode Tabs --}}
    <div style="display:flex;gap:8px;padding:12px 16px;border-bottom:1px solid rgba(148, 163, 184, 0.1);flex-wrap:wrap;">
        <button @click="tab = 'comparison'; setTimeout(() => window.dispatchEvent(new Event('resize')), 50)"
            :style="tab === 'comparison' ? 'background:var(--p-1, #4f46e5);color:#fff;' : 'background:rgba(0,0,0,0.05);color:gray;'"
            style="font-size:11px;font-weight:700;padding:6px 14px;border-radius:100px;cursor:pointer;border:none;">
            Comparación por Turno
        </button>
        <button @click="tab = 'trend'; setTimeout(() => window.dispatchEvent(new Event('resize')), 50)"
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

    @script
    <script>
        window.addEventListener('init-production-chart', (event) => {
            const { uid, data } = event.detail;
            
            const init = () => {
                if (typeof ApexCharts === 'undefined') {
                    setTimeout(init, 300);
                    return;
                }

                // Comparison
                const cmpEl = document.getElementById(uid + '-cmp');
                if (cmpEl && !cmpEl._chart) {
                    cmpEl._chart = new ApexCharts(cmpEl, {
                        chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                        series: [{ name: 'Producción', data: data.compValues }],
                        xaxis: { categories: data.compNames, labels: { style: { fontWeight: '700' } } },
                        colors: data.palette,
                        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
                        dataLabels: { enabled: true, style: { fontWeight: '900' }, formatter: v => Number(v).toLocaleString() },
                        grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
                        yaxis: { labels: { formatter: v => Number(v).toLocaleString() } }
                    });
                    cmpEl._chart.render();
                }

                // Trend
                const trnEl = document.getElementById(uid + '-trn');
                if (trnEl && !trnEl._chart) {
                    trnEl._chart = new ApexCharts(trnEl, {
                        chart: { 
                            type: 'line', 
                            height: 350, 
                            toolbar: { 
                                show: true,
                                tools: {
                                    download: false,
                                    selection: true,
                                    zoom: true,
                                    zoomin: true,
                                    zoomout: true,
                                    pan: true,
                                    reset: true
                                }
                            }, 
                            fontFamily: 'Outfit, sans-serif' 
                        },
                        series: data.trendSeries,
                        xaxis: { categories: data.trendLabels, labels: { style: { fontWeight: '600' } } },
                        colors: data.palette,
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
            };
            
            init();
        });
    </script>
    @endscript
</div>
