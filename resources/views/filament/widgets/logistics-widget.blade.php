@php $d = $this->getChartData(); $uid = 'chart-log-'.uniqid(); @endphp
<div>
    <div id="{{ $uid }}" style="min-height:340px;width:100%;"></div>
    @script
    <script>
    (function(){
        var el = document.getElementById('{{ $uid }}');
        if (!el) return;
        function init() {
            if (typeof ApexCharts === 'undefined') { setTimeout(init, 200); return; }
            var trips = @json($d['trips']);
            var names = @json($d['names']);
            
            // Fix for NaN or empty arrays
            if (!trips || trips.length === 0 || trips.every(t => t === 0)) {
                el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:200px;color:#94a3b8;font-size:13px;font-family:Outfit,sans-serif;">Sin viajes registrados en el período</div>';
                return;
            }
            
            var maxT = Math.max.apply(null, trips) || 1;
            
            new ApexCharts(el, {
                chart: { type: 'bar', height: 340, toolbar: { show: false }, fontFamily: 'Outfit, sans-serif' },
                series: [{ name: 'Viajes', data: trips }],
                xaxis: { categories: names, tickAmount: Math.min(maxT, 5), labels: { style: { fontWeight: '700', fontSize: '11px', colors: '#64748b' }, formatter: function(v){ return isNaN(v) ? v : Math.round(v); } }, axisBorder: { show: false } },
                yaxis: { max: maxT + 1, labels: { formatter: function(v){ return isNaN(v) ? v : Math.round(v); }, style: { fontWeight: '800', colors: '#475569' } } },
                plotOptions: { bar: { horizontal: true, borderRadius: 6, dataLabels: { position: 'center' }, barHeight: '55%' } },
                colors: ['#3b82f6'],
                dataLabels: {
                    enabled: true,
                    style: { fontSize: '12px', fontWeight: '800', colors: ['#ffffff'], fontFamily: 'Outfit, sans-serif' },
                    formatter: function(v){ return v > 0 ? v + (v===1?' viaje':' viajes') : '0 viajes'; },
                    dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.5 }
                },
                grid: { borderColor: '#f1f5f9', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
                tooltip: { theme: 'dark', y: { formatter: function(v){ return v + ' despachos finalizados'; } } }
            }).render();
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
    })();
    </script>
    @endscript
</div>
