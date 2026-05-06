<x-filament-panels::page>
    @php
        $d = $this->getReportData();
    @endphp

    <style>
        .rg{font-family:'Outfit',sans-serif;margin:-20px;padding:16px}
        .rg-hdr{background:linear-gradient(135deg,#4f46e5,#312e81);border-radius:14px;padding:20px 24px;color:#fff;margin-bottom:16px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:14px}
        .rg-hdr h1{font-size:20px;font-weight:900;margin:0}
        .rg-hdr p{font-size:11px;opacity:.7;margin:3px 0 0}
        .rg-kpis{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px}
        @media(min-width:1024px){.rg-kpis{grid-template-columns:repeat(4,1fr)}}
        .rg-k{background:#fff;padding:16px;border-radius:12px;border:1px solid #e2e8f0;border-left:4px solid var(--kc)}
        .dark .rg-k{background:#0f172a;border-color:rgba(255,255,255,.06);border-left-color:var(--kc)}
        .rg-k-l{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b}
        .rg-k-v{font-size:22px;font-weight:900;color:#0f172a;margin:4px 0}.dark .rg-k-v{color:#f8fafc}
        .rg-k-t{font-size:9px;font-weight:700;padding:3px 8px;border-radius:6px}
        .rg-row{display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:16px}
        @media(min-width:1024px){.rg-row.r2{grid-template-columns:1fr 1fr}}
        .rg-c{background:#fff;border-radius:12px;padding:16px;border:1px solid #e2e8f0}
        .dark .rg-c{background:#0f172a;border-color:rgba(255,255,255,.06)}
        .rg-ct{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid #f1f5f9}
        .dark .rg-ct{border-bottom-color:rgba(255,255,255,.05)}
        .rg-li{display:flex;align-items:center;justify-content:space-between;padding:10px;border-radius:8px;border:1px solid #f1f5f9;margin-bottom:6px}
        .dark .rg-li{background:rgba(255,255,255,.02);border-color:rgba(255,255,255,.05)}
        .rg-av{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:11px;flex-shrink:0}
        .rg-nm{font-size:12px;font-weight:700;color:#334155}.dark .rg-nm{color:#e2e8f0}
        .rg-btn{font-size:10px;font-weight:800;text-transform:uppercase;padding:4px 10px;border-radius:6px;text-decoration:none;display:inline-flex;align-items:center;gap:3px}
        .rg-btn-i{background:rgba(79,70,229,.1);color:#4f46e5}.dark .rg-btn-i{background:rgba(99,102,241,.15);color:#818cf8}
        .rg-btn-b{background:rgba(59,130,246,.1);color:#3b82f6}.dark .rg-btn-b{background:rgba(59,130,246,.15);color:#60a5fa}
        .rg-empty{text-align:center;padding:20px;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase}
        .dark .apexcharts-text{fill:#94a3b8!important}.dark .apexcharts-legend-text{color:#94a3b8!important}
        .dark .apexcharts-gridline{stroke:rgba(255,255,255,.04)!important}
        
        /* Premium Chart Inner Boxes & Lists */
        .rg-ch-box {
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        .dark .rg-ch-box {
            background: rgba(255, 255, 255, 0.03) !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
        }
        .rg-li-box {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }
        .dark .rg-li-box {
            background: rgba(255, 255, 255, 0.02) !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
        }
        .rg-badge {
            font-weight: 900;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            min-width: 42px;
            text-align: center;
        }
        .rg-badge-gray { background: rgba(148, 163, 184, 0.15); color: #475569; }
        .dark .rg-badge-gray { background: rgba(148, 163, 184, 0.25); color: #cbd5e1; }
        .rg-badge-green { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .rg-badge-green { background: rgba(16, 185, 129, 0.25); color: #34d399; }
        .rg-badge-yellow { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .dark .rg-badge-yellow { background: rgba(245, 158, 11, 0.25); color: #fbbf24; }
        .rg-badge-red { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
        .dark .rg-badge-red { background: rgba(239, 68, 68, 0.25); color: #f87171; }
    </style>

    <div class="rg">
        {{-- HEADER --}}
        <div class="rg-hdr">
            <div>
                <h1>Reporte General</h1>
                <p>{{ $d['start_date'] }} — {{ $d['end_date'] }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div>{{ $this->form }}</div>
                <button wire:click="downloadPdf" class="bg-white/90 text-indigo-700 px-4 py-2 rounded-xl font-bold text-xs shadow cursor-pointer flex items-center gap-2">
                    PDF
                </button>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="rg-kpis">
            <div class="rg-k" style="--kc:#10b981"><div class="rg-k-l">Ventas Totales</div><div class="rg-k-v">Q {{ number_format($d['totalSales'], 0) }}</div><span class="rg-k-t" style="background:#ecfdf5;color:#059669">Q {{ number_format($d['totalPaid'], 0) }} Cobrado</span></div>
            <div class="rg-k" style="--kc:#ef4444"><div class="rg-k-l">Costos Directos</div><div class="rg-k-v">Q {{ number_format($d['totalCosts'], 0) }}</div><span class="rg-k-t" style="background:#fef2f2;color:#dc2626">Base Manufactura</span></div>
            <div class="rg-k" style="--kc:#3b82f6"><div class="rg-k-l">Utilidad Neta</div><div class="rg-k-v">Q {{ number_format($d['earnings'], 0) }}</div><span class="rg-k-t" style="background:#eff6ff;color:#2563eb">Margen: {{ number_format($d['margenBruto'], 1) }}%</span></div>
            <div class="rg-k" style="--kc:#a855f7"><div class="rg-k-l">Ticket Promedio</div><div class="rg-k-v">Q {{ number_format($d['ticketPromedio'], 0) }}</div><span class="rg-k-t" style="background:#faf5ff;color:#7e22ce">Eficiencia: {{ number_format($d['eficienciaCobranza'], 0) }}%</span></div>
        </div>

        {{-- CHARTS --}}
        <div class="rg-row r2">
            <div class="rg-c">
                <div class="rg-ct">Flujo de Ventas Diarias</div>
                <div id="rg-ch-area" style="min-height:260px" wire:ignore></div>
            </div>
            <div class="rg-c">
                <div class="rg-ct">Top 5 Productos Rentables</div>
                <div id="rg-ch-bar" style="min-height:260px" wire:ignore></div>
            </div>
        </div>

        {{-- AUDITORÍA --}}
        <div class="rg-row r2">
            <div class="rg-c">
                <div class="rg-ct">Auditoría Vendedores</div>
                @forelse($d['salesByUser'] as $row)
                <div class="rg-li">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="rg-av" style="background:rgba(79,70,229,.1);color:#6366f1">{{ strtoupper(substr($row->name, 0, 1)) }}</div>
                        <div class="min-w-0"><div class="rg-nm truncate">{{ $row->name }}</div><div class="text-[9px] font-bold text-slate-400 dark:text-slate-300">{{ $row->count }} ventas</div></div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">Q {{ number_format($row->total_sales, 0) }}</span>
                        <a href="{{ url('/admin/sales?tableFilters[status][value]=confirmed&tableFilters[sale_date][from]=' . $d['start_raw'] . '&tableFilters[sale_date][until]=' . $d['end_raw'] . '&tableFilters[created_by][value]=' . $row->id) }}" class="rg-btn rg-btn-i">VER</a>
                    </div>
                </div>
                @empty
                <div class="rg-empty">Sin ventas en este período</div>
                @endforelse
            </div>
            <div class="rg-c">
                <div class="rg-ct">Auditoría Pilotos</div>
                @forelse($d['dispatchesByDriver'] as $row)
                <div class="rg-li">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="rg-av" style="background:rgba(59,130,246,.1);color:#3b82f6">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                        </div>
                        <div class="min-w-0"><div class="rg-nm truncate">{{ $row->driver_name }}</div><div class="text-[9px] font-bold text-slate-400 dark:text-slate-300">{{ $row->count }} viajes</div></div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-sm font-black text-blue-600 dark:text-blue-400">{{ $row->count }}</span>
                        @if($row->driver_id)
                        <a href="{{ url('/admin/dispatches?tableFilters[created_at][from]=' . $d['start_raw'] . '&tableFilters[created_at][until]=' . $d['end_raw'] . '&tableFilters[driver_id][value]=' . $row->driver_id) }}" class="rg-btn rg-btn-b">AUDITAR</a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="rg-empty">Sin despachos en este período</div>
                @endforelse
            </div>
        </div>

        {{-- PRODUCCIÓN POR TURNO --}}
        @if($d['productionDetailed']->count() > 0)
        <div class="rg-row" style="margin-top: 16px;">
            <div class="rg-c">
                <div class="rg-ct" style="display: flex; align-items: center; gap: 8px; font-size: 11px; color: #4f46e5; border-bottom: 2px solid rgba(99, 102, 241, 0.1); padding-bottom: 8px;">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3h13.5m-13.5 0v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    <span>Rendimiento y Producción por Turno</span>
                </div>
                
                {{-- Split Layout: Left Chart, Right Details --}}
                <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 14px;" class="lg:grid-cols-12">
                    
                    {{-- Left Column: Interactive Column Chart --}}
                    <div class="rg-ch-box lg:col-span-7" style="border-radius: 12px; padding: 12px;" wire:ignore>
                        <div id="rg-ch-production" style="min-height: 250px;"></div>
                    </div>
                    
                    {{-- Right Column: Detailed List Sidebar --}}
                    <div style="display: flex; flex-direction: column; gap: 10px;" class="lg:col-span-5">
                        @foreach($d['productionDetailed'] as $row)
                            @php
                                $ef = $row->eficiencia;
                                if ($ef === null) {
                                    $badgeBg = 'rgba(148, 163, 184, 0.15)';
                                    $badgeColor = '#64748b';
                                    $efText = 'N/A';
                                } elseif ($ef >= 100) {
                                    $badgeBg = 'rgba(16, 185, 129, 0.15)';
                                    $badgeColor = '#10b981';
                                    $efText = number_format($ef, 0) . '%';
                                } elseif ($ef >= 70) {
                                    $badgeBg = 'rgba(245, 158, 11, 0.15)';
                                    $badgeColor = '#f59e0b';
                                    $efText = number_format($ef, 0) . '%';
                                } else {
                                    $badgeBg = 'rgba(239, 68, 68, 0.15)';
                                    $badgeColor = '#ef4444';
                                    $efText = number_format($ef, 0) . '%';
                                }
                                
                                $shiftNameLower = mb_strtolower($row->shift_name);
                                if (str_contains($shiftNameLower, 'mañana')) {
                                    $shiftIconBg = 'linear-gradient(135deg, #fef08a, #f97316)';
                                    $shiftIcon = '☀️';
                                } elseif (str_contains($shiftNameLower, 'tarde')) {
                                    $shiftIconBg = 'linear-gradient(135deg, #38bdf8, #1d4ed8)';
                                    $shiftIcon = '⛅';
                                } else {
                                    $shiftIconBg = 'linear-gradient(135deg, #475569, #0f172a)';
                                    $shiftIcon = '🌙';
                                }
                            @endphp
                            
                            <div class="rg-li-box" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border-radius: 12px; backdrop-filter: blur(4px); transition: all 0.2s ease;" onmouseover="this.style.transform='translateX(3px)'" onmouseout="this.style.transform='translateX(0)'">
                                <div style="display: flex; align-items: center; gap: 10px; min-w-0;">
                                    <div style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; background: {{$shiftIconBg}}; box-shadow: 0 2px 4px rgba(0,0,0,0.06); flex-shrink: 0;">
                                        {{ $shiftIcon }}
                                    </div>
                                    <div style="min-w-0;">
                                        <div class="font-extrabold text-slate-800 dark:text-slate-100 truncate" style="font-size: 11px; text-transform: uppercase;">
                                            {{ $row->shift_name }}
                                        </div>
                                        <div class="text-slate-400 dark:text-slate-300 truncate" style="font-size: 10px; font-weight: 700; margin-top: 1px;">
                                            {{ $row->product_name }}
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                                    <div style="text-align: right;">
                                        <div class="text-slate-800 dark:text-slate-200 font-extrabold" style="font-size: 13px;">
                                            {{ number_format($row->total_qty) }} <span style="font-size: 8px; font-weight: bold; color: #94a3b8;">u.</span>
                                        </div>
                                        <div class="text-slate-400 dark:text-slate-300" style="font-size: 8px; font-weight: bold;">
                                            {{ $row->operations }} op.
                                        </div>
                                    </div>
                                    <div class="rg-badge rg-badge-{{ $ef === null ? 'gray' : ($ef >= 100 ? 'green' : ($ef >= 70 ? 'yellow' : 'red')) }}">
                                        {{ $efText }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <script>
    var areaChart = null;
    var barChart = null;
    var productionChart = null;

    window.updateAllCharts = function(salesVal, salesCat, prodNames, prodProfits, pCats, pVals, pEfs, pProds) {
        var isDark = document.documentElement.classList.contains('dark');
        var gc = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)';

        // 1. Area Chart (Ventas Diarias)
        var areaEl = document.getElementById('rg-ch-area');
        if (areaEl) {
            if (!areaChart) {
                areaChart = new ApexCharts(areaEl, {
                    series: [{name:'Ventas',data:salesVal}],
                    chart:{type:'area',height:250,toolbar:{show:false},fontFamily:'Outfit',background:'transparent'},
                    theme:{mode:isDark?'dark':'light'},
                    colors:['#6366f1'],
                    stroke:{curve:'smooth',width:3},
                    fill:{type:'gradient',gradient:{opacityFrom:0.4,opacityTo:0.05}},
                    grid:{borderColor:gc,strokeDashArray:3},
                    xaxis:{categories:salesCat,labels:{style:{fontWeight:700,fontSize:'10px'}}},
                    yaxis:{labels:{formatter:function(v){return 'Q '+Math.round(v).toLocaleString()}}},
                    dataLabels:{enabled:false},
                    tooltip:{theme:'dark'}
                });
                areaChart.render();
            } else {
                areaChart.updateOptions({
                    xaxis: {categories: salesCat},
                    theme: {mode: isDark?'dark':'light'},
                    grid: {borderColor: gc}
                });
                areaChart.updateSeries([{name:'Ventas',data:salesVal}]);
            }
        }

        // 2. Bar Chart (Top Productos)
        var barEl = document.getElementById('rg-ch-bar');
        if (barEl) {
            if (!prodNames.length) {
                barEl.innerHTML = '<div style="text-align:center;padding:60px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase">Sin productos vendidos</div>';
                barChart = null;
            } else {
                if (barEl.querySelector('.apexcharts-canvas') === null) {
                    barEl.innerHTML = '';
                    barChart = null;
                }
                if (!barChart) {
                    barChart = new ApexCharts(barEl, {
                        series:[{name:'Utilidad',data:prodProfits}],
                        chart:{type:'bar',height:250,toolbar:{show:false},fontFamily:'Outfit',background:'transparent'},
                        theme:{mode:isDark?'dark':'light'},
                        colors:['#10b981','#6366f1','#f59e0b','#ef4444','#8b5cf6'],
                        plotOptions:{bar:{horizontal:true,borderRadius:6,barHeight:'50%',distributed:true}},
                        xaxis:{categories:prodNames},
                        dataLabels:{enabled:true,formatter:function(v){return 'Q '+Math.round(v).toLocaleString()},style:{fontWeight:900}},
                        grid:{borderColor:gc},
                        legend:{show:false}
                    });
                    barChart.render();
                } else {
                    barChart.updateOptions({
                        xaxis: {categories: prodNames},
                        theme: {mode: isDark?'dark':'light'},
                        grid: {borderColor: gc}
                    });
                    barChart.updateSeries([{name:'Utilidad',data:prodProfits}]);
                }
            }
        }

        // 3. Production Chart
        var productionEl = document.getElementById('rg-ch-production');
        if (productionEl) {
            var pColors = pCats.map(function(c) {
                var lower = c.toLowerCase();
                if (lower.indexOf('mañana') !== -1) return '#f59e0b';
                if (lower.indexOf('tarde') !== -1) return '#3b82f6';
                return '#64748b';
            });

            if (!productionChart) {
                productionChart = new ApexCharts(productionEl, {
                    series: [{name: 'Cantidad Producida', data: pVals}],
                    chart: {type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'Outfit', background: 'transparent'},
                    theme: { mode: isDark ? 'dark' : 'light' },
                    colors: pColors,
                    plotOptions: {bar: {horizontal: false, columnWidth: '35%', borderRadius: 6, distributed: true}},
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opt) {
                            var ef = pEfs[opt.dataPointIndex];
                            return val.toLocaleString() + ' u.' + (ef ? ' (' + ef + '%)' : '');
                        },
                        style: {fontSize: '10px', fontWeight: '800', colors: [isDark ? '#f8fafc' : '#0f172a']}
                    },
                    grid: {borderColor: gc, strokeDashArray: 3},
                    xaxis: {categories: pCats, labels: {style: {fontWeight: 700, fontSize: '10px'}}},
                    yaxis: {labels: {formatter: function(v) { return Math.round(v).toLocaleString() + ' u.' }}},
                    tooltip: {
                        theme: 'dark',
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            var q = pVals[dataPointIndex];
                            var ef = pEfs[dataPointIndex];
                            var prod = pProds[dataPointIndex];
                            var shift = pCats[dataPointIndex];
                            return '<div style="padding: 10px; font-family: Outfit; font-size: 11px; line-height: 1.4;">' +
                                '<div style="font-weight: 800; text-transform: uppercase; color: #a5b4fc; margin-bottom: 4px;">' + shift + '</div>' +
                                '<div><span style="font-weight: bold; color: #94a3b8;">Producto:</span> <span style="font-weight: bold; color: #fff;">' + prod + '</span></div>' +
                                '<div><span style="font-weight: bold; color: #94a3b8;">Cantidad:</span> <span style="font-weight: 800; color: #fff;">' + q.toLocaleString() + ' u.</span></div>' +
                                (ef ? '<div><span style="font-weight: bold; color: #94a3b8;">Eficiencia:</span> <span style="font-weight: 900; color: #10b981;">' + ef + '%</span></div>' : '') +
                                '</div>';
                        }
                    },
                    legend: { show: false }
                });
                productionChart.render();
            } else {
                productionChart.updateOptions({
                    xaxis: {categories: pCats},
                    colors: pColors,
                    theme: {mode: isDark?'dark':'light'},
                    grid: {borderColor: gc},
                    dataLabels: {style: {colors: [isDark ? '#f8fafc' : '#0f172a']}}
                });
                productionChart.updateSeries([{name: 'Cantidad Producida', data: pVals}]);
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        function waitApex(fn) {
            if (typeof ApexCharts !== 'undefined') { fn(); return; }
            setTimeout(function(){ waitApex(fn); }, 300);
        }
        waitApex(function() {
            window.updateAllCharts(
                @json($d['dailySales']->pluck('total')),
                @json($d['dailySales']->map(fn($v) => \Illuminate\Support\Carbon::parse($v->date)->format('d/m'))),
                @json($d['topProducts']->pluck('name')),
                @json($d['topProducts']->pluck('profit')),
                @json($d['productionDetailed']->pluck('shift_name')),
                @json($d['productionDetailed']->pluck('total_qty')),
                @json($d['productionDetailed']->map(fn($r) => $r->eficiencia !== null ? round($r->eficiencia) : null)),
                @json($d['productionDetailed']->pluck('product_name'))
            );
        });
    });
    </script>

    {{-- Refrescador dinámico de Livewire --}}
    <div wire:key="chart-refresher-{{ time() }}">
        <script>
            if (typeof window.updateAllCharts === 'function') {
                window.updateAllCharts(
                    @json($d['dailySales']->pluck('total')),
                    @json($d['dailySales']->map(fn($v) => \Illuminate\Support\Carbon::parse($v->date)->format('d/m'))),
                    @json($d['topProducts']->pluck('name')),
                    @json($d['topProducts']->pluck('profit')),
                    @json($d['productionDetailed']->pluck('shift_name')),
                    @json($d['productionDetailed']->pluck('total_qty')),
                    @json($d['productionDetailed']->map(fn($r) => $r->eficiencia !== null ? round($r->eficiencia) : null)),
                    @json($d['productionDetailed']->pluck('product_name'))
                );
            }
        </script>
    </div>
</x-filament-panels::page>
