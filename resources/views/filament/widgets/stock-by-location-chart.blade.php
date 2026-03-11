@php $d = $this->getChartData(); $uid = 'chart-stkloc-'.uniqid(); @endphp
<div>
    <div class="pb-chart-scrollable">
        <div id="{{ $uid }}" style="min-height:360px;width:100%;min-width:600px;"></div>
    </div>

    <x-filament::modal id="stock-detail-modal" width="5xl" slide-over>
        <x-slot name="heading">
            Detalle de Stock: <span class="text-primary-600">{{ $selectedLocation }}</span>
        </x-slot>

        @if($selectedLocation)
            <div class="py-4">
                @livewire(\App\Filament\Widgets\StockByLocationTable::class, [
                    'locationName' => $selectedLocation
                ], key('detail-'.$selectedLocation))
            </div>
        @endif
    </x-filament::modal>

    @script
    <script>
    (function(){
        var el = document.getElementById('{{ $uid }}');
        if (!el) return;
        function init() {
            if (typeof ApexCharts === 'undefined') { setTimeout(init, 200); return; }
            var chart = new ApexCharts(el, {
                chart: { 
                    type: 'bar', 
                    height: 360, 
                    toolbar: { show: false }, 
                    fontFamily: 'Outfit, sans-serif',
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            var label = config.w.config.xaxis.categories[config.dataPointIndex];
                            $wire.openDetail(label);
                        }
                    }
                },
                series: [{ name: 'Existencia', data: @json($d['values']) }],
                xaxis: { categories: @json($d['labels']), tickAmount: 6, labels: { style: { fontWeight: '700', colors: '#64748b' }, formatter: function(v){ return isNaN(v) ? v : (v >= 1000 ? (v/1000).toFixed(1) + 'k' : Math.round(v)); } }, axisBorder: { show: false } },
                plotOptions: { 
                    bar: { 
                        horizontal: true, 
                        borderRadius: 6, 
                        distributed: true, 
                        dataLabels: { position: 'bottom' }, 
                        barHeight: '60%' 
                    } 
                },
                colors: ['#6366f1','#10b981','#f59e0b','#f43f5e','#8b5cf6','#06b6d4','#ec4899','#0ea5e9'],
                legend: { show: false },
                dataLabels: {
                    enabled: true, 
                    textAnchor: 'start',
                    offsetX: 10,
                    style: { fontSize: '11px', fontWeight: '900', colors: ['#ffffff'] },
                    formatter: function(v){ return Number(v).toLocaleString() + ' u'; },
                    dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.8 }
                },
                grid: { 
                    borderColor: '#f1f5f9', 
                    strokeDashArray: 4, 
                    xaxis: { lines: { show: true } }, 
                    yaxis: { lines: { show: false } },
                    padding: { right: 50, left: 10 } 
                },
                yaxis: { labels: { maxWidth: 160, style: { fontWeight: '800', colors: '#475569' } } },
                tooltip: { theme: 'dark', y: { formatter: function(v){ return Number(v).toLocaleString() + ' unidades'; } } },
                states: {
                    active: {
                        filter: { type: 'none' }
                    }
                }
            });
            chart.render();
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
    })();
    </script>
    @endscript
</div>
