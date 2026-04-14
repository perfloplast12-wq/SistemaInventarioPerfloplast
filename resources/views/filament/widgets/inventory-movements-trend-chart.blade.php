@php $d = $this->getChartData(); $uid = 'chart-imov-'.uniqid(); @endphp
<div>
    <div class="pb-chart-scrollable">
        <div id="{{ $uid }}" style="min-height:360px;width:100%;min-width:800px;"></div>
    </div>
    @script
    <script>
    (function(){
        var el = document.getElementById('{{ $uid }}');
        if (!el) return;
        function init() {
            if (typeof ApexCharts === 'undefined') { setTimeout(init, 200); return; }
            new ApexCharts(el, {
                chart: { type: 'area', height: 360, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                series: [{ name: 'Movimientos', data: @json($d['seriesData']) }],
                xaxis: { categories: @json($d['categories']), labels: { style: { fontWeight: '700', colors: '#64748b' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                colors: ['#3b82f6'],
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 100] } },
                stroke: { curve: 'smooth', width: 3 },
                dataLabels: {
                    enabled: true,
                    style: { fontSize: '10px', fontWeight: '800', fontFamily: 'Outfit, sans-serif' },
                    background: { enabled: true, foreColor: '#1e293b', padding: 4, borderRadius: 4, borderWidth: 1, borderColor: '#e2e8f0', opacity: 0.9 },
                    formatter: function(v){ return Number(v).toLocaleString(); },
                    dropShadow: { enabled: false }
                },
                yaxis: { labels: { style: { fontWeight: '800', colors: '#475569' }, formatter: function(v){ return Math.round(v); } } },
                grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: true } } },
                tooltip: { theme: 'dark', y: { formatter: function(v){ return Number(v).toLocaleString() + ' movimientos'; } } }
            }).render();
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
    })();
    </script>
    @endscript
</div>
