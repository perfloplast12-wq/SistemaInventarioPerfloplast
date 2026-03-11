@php $d = $this->getChartData(); $uid = 'chart-dist-'.uniqid(); @endphp
<div>
    <div class="pb-chart-scrollable">
        <div id="{{ $uid }}" style="min-height:360px;width:100%;min-width:600px;"></div>
    </div>
    @script
    <script>
    (function(){
        var el = document.getElementById('{{ $uid }}');
        if (!el) return;
        function init() {
            if (typeof ApexCharts === 'undefined') { setTimeout(init, 200); return; }
            var series = @json($d['series']);
            if (!series || series.length === 0) {
                el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:200px;color:#94a3b8;font-size:13px;font-family:Outfit,sans-serif;">Sin stock de materia prima registrado</div>';
                return;
            }
            new ApexCharts(el, {
                chart: { type: 'donut', height: 360, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                series: series,
                labels: @json($d['labels']),
                colors: ['#6366f1','#10b981','#f59e0b','#f43f5e','#8b5cf6','#06b6d4','#ec4899','#0ea5e9'],
                legend: {
                    position: 'bottom', fontWeight: '700',
                    formatter: function(name, opts) {
                        return name + ' - ' + Number(opts.w.globals.series[opts.seriesIndex]).toLocaleString() + ' Sacos';
                    }
                },
                plotOptions: {
                    pie: { donut: { size: '60%', labels: {
                        show: true,
                        name: { fontSize: '12px', fontWeight: '700', color: '#64748b' },
                        value: { fontWeight: '900', fontSize: '20px', color: '#1e293b', formatter: function(v){ return Number(v).toLocaleString() + ' Sacos'; } },
                        total: { show: true, label: 'Total Stock MP', fontWeight: '800', color: '#64748b', fontSize: '13px',
                            formatter: function(w){ return Number(w.globals.seriesTotals.reduce(function(a,b){ return a+b; }, 0)).toLocaleString() + ' Sacos'; } }
                    }}}
                },
                dataLabels: { 
                    enabled: true, 
                    style: { fontSize: '11px', fontWeight: '800', fontFamily: 'Outfit, sans-serif' }, 
                    background: { enabled: true, foreColor: '#1e293b', padding: 5, borderRadius: 4, borderWidth: 1, borderColor: '#e2e8f0', opacity: 0.95 },
                    dropShadow: { enabled: false },
                    formatter: function(val, opts) { 
                        var kg = Number(opts.w.globals.series[opts.seriesIndex]).toLocaleString();
                        return Math.round(val) + '% (' + kg + ' Sacos)'; 
                    } 
                },
                stroke: { width: 3, colors: ['#ffffff'] },
                tooltip: { theme: 'dark', y: { formatter: function(v){ return Number(v).toLocaleString() + ' Sacos en inventario'; } } }
            }).render();
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
    })();
    </script>
    @endscript
</div>
