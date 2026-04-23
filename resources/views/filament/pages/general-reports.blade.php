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
        <div class="rg-row r2" wire:ignore>
            <div class="rg-c">
                <div class="rg-ct">Flujo de Ventas Diarias</div>
                <div id="rg-ch-area" style="min-height:260px"></div>
            </div>
            <div class="rg-c">
                <div class="rg-ct">Top 5 Productos Rentables</div>
                <div id="rg-ch-bar" style="min-height:260px"></div>
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
                        <div class="min-w-0"><div class="rg-nm truncate">{{ $row->name }}</div><div class="text-[9px] font-bold text-slate-400">{{ $row->count }} ventas</div></div>
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
                        <div class="min-w-0"><div class="rg-nm truncate">{{ $row->driver_name }}</div><div class="text-[9px] font-bold text-slate-400">{{ $row->count }} viajes</div></div>
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

        {{-- PRODUCCIÓN --}}
        @if($d['productionDetailed']->count() > 0)
        <div class="rg-row">
            <div class="rg-c" style="overflow-x:auto">
                <div class="rg-ct">Producción por Turno</div>
                <table style="width:100%;border-collapse:collapse;font-size:12px">
                    <thead><tr style="border-bottom:2px solid #e2e8f0">
                        <th style="text-align:left;padding:6px 10px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b">Turno</th>
                        <th style="text-align:left;padding:6px 10px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b">Producto</th>
                        <th style="text-align:right;padding:6px 10px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b">Cantidad</th>
                        <th style="text-align:right;padding:6px 10px;font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b">Eficiencia</th>
                    </tr></thead>
                    <tbody>
                        @foreach($d['productionDetailed'] as $row)
                        <tr style="border-bottom:1px solid #f1f5f9">
                            <td style="padding:6px 10px;font-weight:700" class="dark:text-slate-300">{{ $row->shift_name }}</td>
                            <td style="padding:6px 10px;color:#64748b" class="dark:text-slate-400">{{ $row->product_name }}</td>
                            <td style="padding:6px 10px;text-align:right;font-weight:800" class="dark:text-white">{{ number_format($row->total_qty) }}</td>
                            <td style="padding:6px 10px;text-align:right">
                                @if($row->eficiencia !== null)
                                <span style="font-weight:800;font-size:10px;padding:2px 6px;border-radius:6px;{{ $row->eficiencia >= 100 ? 'background:#ecfdf5;color:#059669' : ($row->eficiencia >= 70 ? 'background:#fefce8;color:#ca8a04' : 'background:#fef2f2;color:#dc2626') }}">{{ number_format($row->eficiencia, 0) }}%</span>
                                @else
                                <span style="color:#94a3b8;font-size:10px">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function waitApex(fn) {
            if (typeof ApexCharts !== 'undefined') { fn(); return; }
            setTimeout(function(){ waitApex(fn); }, 300);
        }

        waitApex(function() {
            var isDark = document.documentElement.classList.contains('dark');
            var gc = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)';

            // Area Chart
            var areaEl = document.getElementById('rg-ch-area');
            if (areaEl) {
                new ApexCharts(areaEl, {
                    series: [{name:'Ventas',data:@json($d['dailySales']->pluck('total'))}],
                    chart:{type:'area',height:250,toolbar:{show:false},fontFamily:'Outfit',background:'transparent'},
                    theme:{mode:isDark?'dark':'light'},
                    colors:['#6366f1'],
                    stroke:{curve:'smooth',width:3},
                    fill:{type:'gradient',gradient:{opacityFrom:0.4,opacityTo:0.05}},
                    grid:{borderColor:gc,strokeDashArray:3},
                    xaxis:{categories:@json($d['dailySales']->map(function($v){return \Illuminate\Support\Carbon::parse($v->date)->format('d/m');})),labels:{style:{fontWeight:700,fontSize:'10px'}}},
                    yaxis:{labels:{formatter:function(v){return 'Q '+Math.round(v).toLocaleString()}}},
                    dataLabels:{enabled:false},
                    tooltip:{theme:'dark'}
                }).render();
            }

            // Bar Chart
            var barEl = document.getElementById('rg-ch-bar');
            if (barEl) {
                var cats = @json($d['topProducts']->pluck('name'));
                var vals = @json($d['topProducts']->pluck('profit'));
                if (!cats.length) {
                    barEl.innerHTML = '<div style="text-align:center;padding:60px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase">Sin productos vendidos</div>';
                } else {
                    new ApexCharts(barEl, {
                        series:[{name:'Utilidad',data:vals}],
                        chart:{type:'bar',height:250,toolbar:{show:false},fontFamily:'Outfit'},
                        theme:{mode:isDark?'dark':'light'},
                        colors:['#10b981','#6366f1','#f59e0b','#ef4444','#8b5cf6'],
                        plotOptions:{bar:{horizontal:true,borderRadius:6,barHeight:'50%',distributed:true}},
                        xaxis:{categories:cats},
                        dataLabels:{enabled:true,formatter:function(v){return 'Q '+Math.round(v).toLocaleString()},style:{fontWeight:900}},
                        grid:{borderColor:gc},
                        legend:{show:false}
                    }).render();
                }
            }
        });
    });
    </script>
</x-filament-panels::page>
