@php
    $s = $this->getStatsData();
    $activePeriod = $this->activePeriod;
    $showCustom = $this->showCustom;
    $fk = md5(($this->filters['startDate']??'').'|'.($this->filters['endDate']??''));
@endphp

<x-filament-panels::page>
    <div id="pbi-dashboard-root" class="pbi-wrap">
        {{-- ── HEADER Y FILTROS ── --}}
        <div class="pb-hdr">
            <div>
                <div class="pb-breadcrumbs">
                    <a href="{{ url('/') }}">Inicio</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    <span>Dashboard</span>
                </div>
                <div class="pb-title">Dashboard <em>Gerencial</em></div>
                <div class="pb-meta">Perflo-Plast ERP &bull; Datos en tiempo real &bull; {{ now()->translatedFormat('l, d F Y') }}</div>
            </div>
            <div class="pb-slicer">
                <span class="pb-slicer-lbl">Período</span>
                <button wire:click="setPeriod('today')"      class="pb-btn {{ $activePeriod==='today'      ? 'on':'' }}">Hoy</button>
                <button wire:click="setPeriod('yesterday')"  class="pb-btn {{ $activePeriod==='yesterday'  ? 'on':'' }}">Ayer</button>
                <button wire:click="setPeriod('this_week')"  class="pb-btn {{ $activePeriod==='this_week'  ? 'on':'' }}">Semana</button>
                <button wire:click="setPeriod('this_month')" class="pb-btn {{ $activePeriod==='this_month' ? 'on':'' }}">Mes</button>
                <button wire:click="setPeriod('this_year')"  class="pb-btn {{ $activePeriod==='this_year'  ? 'on':'' }}">Año</button>
                <button wire:click="setPeriod('custom')"     class="pb-btn {{ $activePeriod==='custom'     ? 'on':'' }}">Rango</button>
                @if($showCustom)
                <div style="display:flex;gap:6px;align-items:center;padding-left:10px;border-left:1px solid #e2e8f0;">
                    <input wire:model.lazy="customStart" type="date" class="pb-date-in">
                    <span style="color:#64748b;font-size:10px;">→</span>
                    <input wire:model.lazy="customEnd"   type="date" class="pb-date-in">
                    <button wire:click="applyCustomDates" class="pb-btn on">OK</button>
                </div>
                @endif
            </div>
        </div>

        {{-- ── KPIs ── --}}
        <div class="pb-kpi-row">
            <div class="pb-kpi" style="--kc:#6366f1">
                <div class="pb-kpi-bar"></div>
                <div class="pb-kpi-ico"><svg width="14" height="14" fill="none" stroke="#6366f1" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div class="pb-kpi-lbl">Ingresos</div>
                <div class="pb-kpi-val">{{ $s['sales'] }}</div>
                <div class="pb-kpi-sub pb-flex-between">
                    <span>Ventas confirmadas</span>
                    @if(isset($s['salesTrend']))
                        <span class="pb-trend pb-trend-{{ $s['salesTrend']['dir'] }}" title="frente a periodo anterior">
                            @if($s['salesTrend']['dir'] === 'up')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>@elseif($s['salesTrend']['dir'] === 'down')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>@else <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 12h14"/></svg> @endif
                            {{ $s['salesTrend']['value'] }}%
                        </span>
                    @endif
                </div>
            </div>
            <div class="pb-kpi" style="--kc:{{ (isset($s['profitRaw']) && $s['profitRaw']>=0) ? '#10b981':'#f43f5e' }}">
                <div class="pb-kpi-bar"></div>
                <div class="pb-kpi-lbl">Ganancia Neta</div>
                <div class="pb-kpi-val">{{ $s['profit'] }}</div>
                <div class="pb-kpi-sub pb-flex-between">
                    <span>Margen bruto</span>
                    @if(isset($s['profitTrend']))
                        <span class="pb-trend pb-trend-{{ $s['profitTrend']['dir'] }}" title="frente a periodo anterior">
                            @if($s['profitTrend']['dir'] === 'up')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>@elseif($s['profitTrend']['dir'] === 'down')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>@else <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 12h14"/></svg> @endif
                            {{ $s['profitTrend']['value'] }}%
                        </span>
                    @endif
                </div>
            </div>
            <div class="pb-kpi" style="--kc:#f59e0b">
                <div class="pb-kpi-bar"></div>
                <div class="pb-kpi-ico"><svg width="14" height="14" fill="none" stroke="#f59e0b" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg></div>
                <div class="pb-kpi-lbl">Producción</div>
                <div class="pb-kpi-val">{{ $s['production'] }}</div>
                <div class="pb-kpi-sub pb-flex-between">
                    <span>Unidades confirmadas</span>
                    @if(isset($s['prodTrend']))
                        <span class="pb-trend pb-trend-{{ $s['prodTrend']['dir'] }}" title="frente a periodo anterior">
                            @if($s['prodTrend']['dir'] === 'up')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>@elseif($s['prodTrend']['dir'] === 'down')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>@else <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 12h14"/></svg> @endif
                            {{ $s['prodTrend']['value'] }}%
                        </span>
                    @endif
                </div>
            </div>
            <div class="pb-kpi" style="--kc:#06b6d4">
                <div class="pb-kpi-bar"></div>
                <div class="pb-kpi-lbl">Inventario Est.</div>
                <div class="pb-kpi-val">{{ $s['inventory'] }}</div>
                <div class="pb-kpi-sub">Valor en bodega (Q)</div>
            </div>
            <div class="pb-kpi" style="--kc:#8b5cf6">
                <div class="pb-kpi-bar"></div>
                <div class="pb-kpi-lbl">Órdenes Pend.</div>
                <div class="pb-kpi-val">{{ $s['orders'] }}</div>
                <div class="pb-kpi-sub">Sin despachar</div>
            </div>
            <div class="pb-kpi" style="--kc:#ec4899">
                <div class="pb-kpi-bar"></div>
                <div class="pb-kpi-lbl">Eficiencia Log.</div>
                <div class="pb-kpi-val">{{ $s['efficiency'] }}</div>
                <div class="pb-kpi-sub">{{ $s['dispatches'] }} despachos activos</div>
            </div>
        </div>

        {{-- ── FILA 1 ── --}}
        <div class="pb-grid">
            <div class="gc-8">
                <div class="pb-tile-hdr">
                    <div><div class="pb-tile-hdr-title">Ventas vs Producción</div><div class="pb-tile-hdr-sub">Ingresos (Q) · Unidades · Doble Eje</div></div>
                    <span class="pb-tile-hdr-badge">Comercial</span>
                </div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\SalesByPeriodChart::class, ['filters' => $this->filters], key('s1-'.$fk))</div>
            </div>
            <div class="gc-4">
                <div class="pb-tile-hdr">
                    <div><div class="pb-tile-hdr-title">Alertas de Suministro</div><div class="pb-tile-hdr-sub">Normal / Alerta / Crítico</div></div>
                    <span class="pb-tile-hdr-badge">{{ $s['lowStock'] }} alerta</span>
                </div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\InventoryStatusChart::class, ['filters' => $this->filters], key('s2-'.$fk))</div>
            </div>
        </div>

        {{-- ── FILA 2 ── --}}
        <div class="pb-grid">
            <div class="gc-4">
                <div class="pb-tile-hdr"><div><div class="pb-tile-hdr-title">Tendencia Rentabilidad</div><div class="pb-tile-hdr-sub">Ingresos vs Ganancia Neta</div></div></div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\ProfitabilityTrendChart::class, ['filters' => $this->filters], key('s3-'.$fk))</div>
            </div>
            <div class="gc-4">
                <div class="pb-tile-hdr"><div><div class="pb-tile-hdr-title">Stock por Ubicación</div><div class="pb-tile-hdr-sub">Bodegas y Camiones</div></div></div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\StockByLocationChart::class, ['filters' => $this->filters], key('s4-'.$fk))</div>
            </div>
            <div class="gc-4">
                <div class="pb-tile-hdr">
                    <div><div class="pb-tile-hdr-title">Ranking de Operadores Logísticos</div><div class="pb-tile-hdr-sub">Rendimiento por Despachos Finalizados</div></div>
                    <span class="pb-tile-hdr-badge">Top Gp</span>
                </div>
                <div class="pb-widget" style="padding:0">@livewire(\App\Filament\Widgets\LogisticsRankingWidget::class, ['filters' => $this->filters], key('s5-'.$fk))</div>
            </div>
        </div>

        {{-- ── FILA 3 ── --}}
        <div class="pb-grid">
            <div class="gc-6">
                <div class="pb-tile-hdr">
                    <div><div class="pb-tile-hdr-title">Panel Logístico — Viajes por Camión</div><div class="pb-tile-hdr-sub">Despachos por vehículo en el período</div></div>
                    <span class="pb-tile-hdr-badge">Logística</span>
                </div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\LogisticsWidget::class, ['filters' => $this->filters], key('s6-'.$fk))</div>
            </div>
            <div class="gc-6">
                <div class="pb-tile-hdr">
                    <div><div class="pb-tile-hdr-title">Top 5 Productos por Rentabilidad</div><div class="pb-tile-hdr-sub">Mayor margen bruto en ventas confirmadas</div></div>
                    <span class="pb-tile-hdr-badge">Comercial</span>
                </div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\TopSellingProductsChart::class, ['filters' => $this->filters], key('s7-'.$fk))</div>
            </div>
        </div>

        {{-- ── FILA 4 ── --}}
        <div class="pb-grid">
            <div class="gc-5">
                <div class="pb-tile-hdr"><div><div class="pb-tile-hdr-title">Distribución Materia Prima</div><div class="pb-tile-hdr-sub">% Stock por tipo de insumo (kg)</div></div></div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\InventoryDistributionChart::class, ['filters' => $this->filters], key('s8-'.$fk))</div>
            </div>
            <div class="gc-7">
                <div class="pb-tile-hdr"><div><div class="pb-tile-hdr-title">Actividad de Almacén</div><div class="pb-tile-hdr-sub">Movimientos de inventario por día</div></div></div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\InventoryMovementsTrendChart::class, ['filters' => $this->filters], key('s9-'.$fk))</div>
            </div>
        </div>

        {{-- ── FILA 5 ── --}}
        <div class="pb-grid">
            <div class="gc-12">
                <div class="pb-tile-hdr">
                    <div><div class="pb-tile-hdr-title">Rendimiento Operativo por Turno</div><div class="pb-tile-hdr-sub">Comparación · Tendencia</div></div>
                    <span class="pb-tile-hdr-badge">Producción</span>
                </div>
                <div class="pb-widget">@livewire(\App\Filament\Widgets\ProductionByShiftChart::class, ['filters' => $this->filters], key('s10-'.$fk))</div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
