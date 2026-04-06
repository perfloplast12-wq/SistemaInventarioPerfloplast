<div x-data="{
    chart: null,
    init() {
        this.render();
        Livewire.hook('commit', ({ component, succeed }) => {
            succeed(() => {
                this.$nextTick(() => { this.render(); });
            });
        });
    },
    render() {
        if (this.chart) this.chart.destroy();
        const data = @js($this->getChartData());
        this.chart = new ApexCharts(this.$refs.canvas, {
            chart: { 
                type: 'donut', 
                height: 450, 
                toolbar: { show: false }, 
                fontFamily: 'Outfit, sans-serif',
                events: {
                    dataPointSelection: (event, chartContext, config) => {
                        var label = config.w.config.labels[config.dataPointIndex];
                        $wire.openDetail(label);
                    }
                }
            },
            series: data.values,
            labels: data.labels,
            stroke: { show: false },
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%',
                        labels: {
                            show: true,
                            name: { show: true, fontSize: '14px', fontWeight: '600', color: '#64748b', offsetY: -10 },
                            value: { 
                                show: true, 
                                fontSize: '22px', 
                                fontWeight: '800', 
                                color: '#1e293b', 
                                offsetY: 10,
                                formatter: function(v){ return Number(v).toLocaleString() + ' u'; }
                            },
                            total: {
                                show: true,
                                label: 'Total Stock',
                                fontSize: '12px',
                                fontWeight: '600',
                                color: '#64748b',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString() + ' u';
                                }
                            }
                        }
                    }
                }
            },
            colors: ['#6366f1','#10b981','#f59e0b','#f43f5e','#8b5cf6','#06b6d4','#ec4899','#0ea5e9'],
            legend: { 
                show: true, 
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '12px',
                fontWeight: '600',
                labels: { colors: '#64748b' },
                markers: { radius: 12 }
            },
            dataLabels: { enabled: false },
            tooltip: { 
                theme: 'dark', 
                y: { 
                    formatter: function(v){ return v.toFixed(2) + ' unidades'; } 
                } 
            },
            states: { active: { filter: { type: 'none' } } }
        });
        this.chart.render();
    }
}">
    <div class="flex justify-center items-center">
        <div x-ref="canvas" wire:ignore style="min-height:450px; width:100%; max-width:600px;"></div>
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
</div>
