<div>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

<div class="pbi-wrap">

    {{-- HEADER --}}
    <div class="pb-hdr" style="margin-bottom: 8px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:42px;height:42px;background:var(--pb-accent);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 4px 12px rgba(79,70,229,.3);">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px;"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <div>
                <div class="pb-title">Control <em>Inventario</em></div>
                <div class="pb-meta">ERP Perflo-Plast &bull; Operaciones Centrales</div>
            </div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ $this->getQuickSaleUrl() }}" class="pb-btn" style="background:var(--pb-card);border:1px solid var(--pb-border);box-shadow:var(--pb-sh);color:var(--pb-text);display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" /></svg>
                Venta
            </a>
            <a href="{{ $this->getMovementCreateUrl('in') }}" class="pb-btn on" style="display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Ingreso
            </a>
        </div>
    </div>

    {{-- KPI GRID --}}
    @php
        $kpis = [
            ['label' => 'Productos Activos',  'value' => $this->totalProducts,                                   'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z', 'sub' => 'Variedad total',       'trend' => '+2.4%',    'up' => true],
            ['label' => 'Valor Inventario',   'value' => 'Q'.number_format($this->inventoryValue/1000, 1).'k',   'icon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'sub' => 'Costo total activos', 'trend' => '+8.1%',    'up' => true],
            ['label' => 'Pedidos Pend.',      'value' => $this->pendingOrdersCount,                              'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z', 'sub' => 'Por despachar',       'trend' => 'Atención', 'up' => false],
            ['label' => 'Movimientos 24h',    'value' => $this->last24hMovements,                                'icon' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5', 'sub' => 'Actividad reciente',  'trend' => '+15%',     'up' => true],
            ['label' => 'Existencia M/P',     'value' => $this->rawMaterials,                                    'icon' => 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 001.357 2.059l4.31 1.72m0-9.493c.251.023.501.05.75.082', 'sub' => 'Insumos base',          'trend' => 'Estable',  'up' => null],
            ['label' => 'Stock en Riesgo',    'value' => $this->criticalStockCount,                              'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z', 'sub' => 'Bajo el umbral',      'trend' => 'Revisar',  'up' => false],
        ];
    @endphp

    <div class="pb-kpi-row">
        @foreach($kpis as $k)
        <div class="pb-kpi" style="--kc:{{ $k['up'] === true ? '#10b981' : ($k['up'] === false ? '#f59e0b' : '#64748b') }}">
            <div class="pb-kpi-bar"></div>
            <div class="pb-kpi-ico">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="{{ $k['up'] === true ? '#10b981' : ($k['up'] === false ? '#f59e0b' : '#64748b') }}" class="kpi-icon"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $k['icon'] }}" /></svg>
            </div>
            <div class="pb-kpi-lbl">{{ $k['label'] }}</div>
            <div class="pb-kpi-val">{{ $k['value'] }}</div>
            <div class="pb-kpi-sub pb-flex-between" style="margin-top:2px;">
                <span>{{ $k['sub'] }}</span>
                @if($k['up'] === true)
                    <span class="pb-trend pb-trend-up">{{ $k['trend'] }}</span>
                @elseif($k['up'] === false)
                    <span class="pb-trend pb-trend-down" style="background:rgba(245,158,11,0.15);color:#d97706">{{ $k['trend'] }}</span>
                @else
                    <span class="pb-trend pb-trend-flat">{{ $k['trend'] }}</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- MAIN 3-COLUMN LAYOUT --}}
    <div class="pb-grid" style="align-items:start;">

        {{-- LEFT PANEL (Col 1) --}}
        <div class="gc-4" style="display:flex;flex-direction:column;gap:16px;">

            {{-- Terminal Maestro --}}
            <div>
                <div class="section-label" style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.12em;color:var(--pb-muted);display:flex;align-items:center;gap:8px;margin-bottom:12px;">Terminal Maestro<div style="flex:1;height:1px;background:var(--pb-border);"></div></div>
                <div style="display:grid;grid-template-columns:1fr;gap:10px;">
                    @foreach([
                        ['t' => 'Materia Prima',   's' => 'Gestión de insumos',       'u' => $this->getRawMaterialsIndexUrl(),    'icon' => 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5'],
                        ['t' => 'Catálogo Final',  's' => 'Productos terminados',     'u' => $this->getFinishedProductsIndexUrl(),'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
                        ['t' => 'Historial',       's' => 'Movimientos históricos',   'u' => $this->getMovementsUrl('in'),        'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
                        ['t' => 'Kardex',          's' => 'Control de existencias',   'u' => $this->getKardexUrl(),               'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z'],
                        ['t' => 'Devoluciones',    's' => 'Rechazos y Entradas',      'u' => $this->getReturnsUrl(),              'icon' => 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3'],
                    ] as $nav)
                    <a href="{{ $nav['u'] }}" class="pb-card group" style="padding:14px;display:flex;align-items:center;gap:12px;text-decoration:none;">
                        <div style="width:34px;height:34px;border-radius:10px;background:var(--pb-subtle,#f8fafc);display:flex;align-items:center;justify-content:center;color:var(--pb-muted);transition:all .2s;" class="group-hover:bg-indigo-600 group-hover:text-white flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $nav['icon'] }}" /></svg>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px;font-weight:900;color:var(--pb-text);line-height:1.2;transition:color .2s;" class="group-hover:text-indigo-600">{{ $nav['t'] }}</div>
                            <div style="font-size:10px;color:var(--pb-muted);font-weight:500;">{{ $nav['s'] }}</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;color:var(--pb-border);flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Acciones Rápidas --}}
            <div>
                <div class="section-label" style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.12em;color:var(--pb-muted);display:flex;align-items:center;gap:8px;margin-bottom:12px;">Acciones Rápidas<div style="flex:1;height:1px;background:var(--pb-border);"></div></div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <a href="{{ $this->getMovementsUrl('adjust') }}" class="pb-card group" style="padding:14px;display:flex;align-items:center;justify-content:space-between;text-decoration:none;gap:14px;">
                        <div style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:rgba(217,119,6,0.1);color:#d97706">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                        </div>
                        <div style="flex:1">
                            <div style="font-size:13px;font-weight:900;color:var(--pb-text);line-height:1;transition:color .2s;" class="group-hover:text-amber-600">Ajuste Manual</div>
                            <div style="font-size:10px;font-weight:500;color:var(--pb-muted);margin-top:2px;">Corrección de stocks</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;color:var(--pb-border);flex-shrink:0;transition:color .2s;" class="group-hover:text-amber-600"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </a>
                    <a href="{{ $this->getMovementsUrl('transfer') }}" class="pb-card group" style="padding:14px;display:flex;align-items:center;justify-content:space-between;text-decoration:none;gap:14px;">
                        <div style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:rgba(3,105,161,0.1);color:#0369a1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                        </div>
                        <div style="flex:1">
                            <div style="font-size:13px;font-weight:900;color:var(--pb-text);line-height:1;transition:color .2s;" class="group-hover:text-sky-600">Traslado de Bodega</div>
                            <div style="font-size:10px;font-weight:500;color:var(--pb-muted);margin-top:2px;">Mover mercaderías</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;color:var(--pb-border);flex-shrink:0;transition:color .2s;" class="group-hover:text-sky-600"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- CENTER PANEL (Col 2 & 3) --}}
        <div class="gc-8" style="display:flex;flex-direction:column;gap:16px;">

            {{-- Supply Alerts --}}
            <div class="pb-card" style="border-left:4px solid #f43f5e;overflow:hidden;border-radius:14px;">
                <div style="padding:14px 18px;border-bottom:1px solid var(--pb-border);background:var(--pb-subtle);display:flex;align-items:center;gap:12px;">
                    <div style="width:34px;height:34px;background:rgba(244,63,94,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#f43f5e;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:900;color:var(--pb-text);line-height:1.2;">Alertas de Suministro</div>
                        <div style="font-size:10px;font-weight:500;color:var(--pb-muted);margin-top:2px;">{{ $this->criticalStockCount }} productos requieren atención inmediata</div>
                    </div>
                </div>
                
                {{-- Modern Rows instead of traditional table --}}
                <div style="display:flex;flex-direction:column;">
                    <div style="display:flex;padding:10px 14px;background:var(--pb-bg);border-bottom:1px solid var(--pb-border);font-size:9px;font-weight:900;color:var(--pb-muted);text-transform:uppercase;letter-spacing:.08em;">
                        <div style="flex:0 0 60px;">Estado</div>
                        <div style="flex:1;">Producto</div>
                        <div style="flex:0 0 70px;text-align:right;">Stock</div>
                        <div style="flex:0 0 75px;text-align:center;">Nivel</div>
                        <div style="flex:0 0 70px;text-align:right;">Acción</div>
                    </div>
                    
                    @forelse($this->getCriticalStockProducts() as $item)
                    @php
                        $threshold = 10;
                        $safeLevel = $threshold * 3;
                        $pct = min(100, max(0, ($item['stock'] / $safeLevel) * 100));
                        $isRose = $item['stock'] <= ($threshold * 0.3);
                        $fillColor = $isRose ? '#f43f5e' : '#f59e0b';
                    @endphp
                    <div style="display:flex;align-items:center;padding:10px 14px;border-bottom:1px solid var(--pb-border);transition:background .2s;" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <div style="flex:0 0 60px;">
                            <span style="font-size:10px;font-weight:900;color:{{ $fillColor }};">{{ $isRose ? 'Crítico' : 'Bajo' }}</span>
                        </div>
                        <div style="flex:1;font-size:12px;font-weight:800;color:var(--pb-text);">{{ $item['name'] }}</div>
                        <div style="flex:0 0 70px;text-align:right;font-size:12px;font-weight:900;color:var(--pb-text);">
                            {{ number_format($item['stock'], 1) }}
                            <span style="font-size:10px;font-weight:600;color:var(--pb-muted);margin-left:2px;">{{ $item['unit'] }}</span>
                        </div>
                        <div style="flex:0 0 75px;display:flex;align-items:center;justify-content:center;gap:6px;">
                            <div style="width:36px;height:4px;background:var(--pb-border);border-radius:2px;overflow:hidden;">
                                <div style="height:100%;background:{{ $fillColor }};width:{{ $pct }}%;border-radius:2px;"></div>
                            </div>
                            <span style="font-size:9px;font-weight:900;color:var(--pb-text);width:22px;text-align:right;">{{ round($pct) }}%</span>
                        </div>
                        <div style="flex:0 0 70px;text-align:right;">
                            <a href="{{ $this->getMovementCreateUrl('in') }}" class="pb-btn" style="border:1px solid var(--pb-border);padding:4px 8px;font-size:9px;">Llenar</a>
                        </div>
                    </div>
                    @empty
                    <div style="padding:30px;text-align:center;color:var(--pb-muted);font-size:12px;font-weight:600;font-style:italic;">✓ Inventario estable — sin alertas activas</div>
                    @endforelse
                </div>
            </div>

            {{-- Warehouses --}}
            <div>
                <div class="section-label">Existencias por Bodega</div>
                <div class="pb-grid" style="gap:16px;">
                    @foreach($this->getWarehouseSummaries() as $wh)
                    <div class="gc-6 pb-card" style="display:flex;flex-direction:column;border-radius:14px;overflow:hidden;">
                        <div class="pb-tile-hdr" style="padding:14px 18px;background:var(--pb-subtle);">
                            <div>
                                <div class="pb-tile-hdr-title" style="font-size:13px;letter-spacing:0;text-transform:none;">{{ $wh['name'] }}</div>
                                <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                                    <div style="width:6px;height:6px;border-radius:50%;background:#10b981;box-shadow:0 0 4px rgba(16,185,129,.5);"></div>
                                    <span style="font-size:9px;font-weight:800;color:#10b981;line-height:1;text-transform:uppercase;letter-spacing:.08em;">Operativo</span>
                                </div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:24px;height:24px;color:var(--pb-muted);opacity:.3;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                        </div>
                        <div style="padding:16px 18px;display:flex;flex-direction:column;gap:16px;">
                            {{-- Dual Stats --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                <div style="background:rgba(14,165,233,0.06);border-radius:10px;padding:12px 14px;border-left:3px solid #0ea5e9;">
                                    <div style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#0ea5e9;margin-bottom:6px;">Insumos</div>
                                    <div style="display:flex;align-items:baseline;gap:4px;">
                                        <span style="font-size:22px;font-weight:900;line-height:1;color:var(--pb-text);">{{ number_format($wh['raw_total'], 0) }}</span>
                                        <span style="font-size:10px;font-weight:700;color:var(--pb-muted);">Sacos</span>
                                    </div>
                                </div>
                                <div style="background:rgba(16,185,129,0.06);border-radius:10px;padding:12px 14px;border-left:3px solid #10b981;">
                                    <div style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#10b981;margin-bottom:6px;">Final</div>
                                    <div style="display:flex;align-items:baseline;gap:4px;">
                                        <span style="font-size:22px;font-weight:900;line-height:1;color:var(--pb-text);">{{ number_format($wh['finished_total'], 0) }}</span>
                                        <span style="font-size:10px;font-weight:700;color:var(--pb-muted);">u</span>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Items Lists --}}
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <div style="font-size:9px;font-weight:900;color:var(--pb-muted);text-transform:uppercase;letter-spacing:.12em;padding-bottom:8px;border-bottom:1px solid var(--pb-border);">Inventario de Materiales</div>
                                @foreach($wh['top_items'] as $itm)
                                @php $w = min(100, ($itm['qty'] / max(1, $wh['raw_total'])) * 100); @endphp
                                <div style="display:flex;flex-direction:column;gap:5px;margin-top:2px;">
                                    <div style="display:flex;justify-content:space-between;align-items:baseline;">
                                        <span style="font-size:12px;font-weight:700;color:var(--pb-text);">{{ $itm['name'] }}</span>
                                        <span style="font-size:12px;font-weight:900;color:var(--pb-text);">{{ number_format($itm['qty'], 0) }} <span style="font-size:10px;font-weight:600;color:var(--pb-muted);">{{ $itm['unit'] }}</span></span>
                                    </div>
                                    <div style="height:3px;background:var(--pb-border);border-radius:2px;overflow:hidden;width:100%;">
                                        <div style="height:100%;width:{{ $w }}%;background:var(--pb-muted);border-radius:2px;"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            {{-- Actions --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px;">
                                <a href="{{ \App\Filament\Pages\WarehouseStockDetail::getUrl(['warehouse' => $wh['id'], 'type' => 'raw_material']) }}" class="pb-btn" style="border:1px solid var(--pb-border);background:transparent;text-align:center;">Detalle Materias</a>
                                <a href="{{ \App\Filament\Pages\WarehouseStockDetail::getUrl(['warehouse' => $wh['id'], 'type' => 'finished_product']) }}" class="pb-btn" style="border:1px solid var(--pb-border);background:transparent;text-align:center;">Detalle Final</a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ACTIVIDAD RECIENTE & GRÁFICOS INFERIORES --}}
    <div class="pb-grid" style="align-items:start;">
        <div class="gc-4">
            <div style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.12em;color:var(--pb-muted);display:flex;align-items:center;margin-bottom:12px;">
                Actividad Reciente
                <div style="flex:1;height:1px;background:var(--pb-border);margin:0 12px;"></div>
            </div>
            <div class="pb-card" style="border-radius:14px;overflow:hidden;">
                <div style="display:flex;flex-direction:column;max-height:450px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--pb-border) transparent;">
                @forelse($this->getRecentMovements() as $mov)
                <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 18px;border-bottom:1px solid var(--pb-border);transition:background .2s;" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                    <span style="font-size:9.5px;font-weight:900;padding:4px 10px;border-radius:20px;white-space:nowrap;flex-shrink:0;margin-top:2px;background:{{ $mov['type_bg'] }};color:{{ $mov['type_color'] }}">{{ $mov['type_label'] }}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12.5px;font-weight:800;color:var(--pb-text);line-height:1.3;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $mov['description'] }}">{{ $mov['description'] }}</div>
                        <div style="display:flex;align-items:center;gap:6px;font-size:10px;color:var(--pb-muted);font-weight:500;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:11px;height:11px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                            <span>{{ $mov['user'] }}</span>
                            <div style="width:3px;height:3px;border-radius:50%;background:var(--pb-border);flex-shrink:0;"></div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:11px;height:11px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ $mov['time_ago'] }}</span>
                        </div>
                    </div>
                    <span style="font-size:11px;font-weight:800;color:var(--pb-text);white-space:nowrap;flex-shrink:0;">{{ $mov['qty'] }} <span style="font-size:10px;color:var(--pb-muted);font-weight:600;">{{ $mov['unit'] }}</span></span>
                </div>
                @empty
                <div style="padding:30px;text-align:center;color:var(--pb-muted);font-size:12px;font-style:italic;font-weight:500;">Sin actividad registrada en las últimas horas.</div>
                @endforelse
            </div>
        </div>
    </div>

        <div class="gc-8">
            <div style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.12em;color:var(--pb-muted);display:flex;align-items:center;margin-bottom:12px;">Gráficos de Control<div style="flex:1;height:1px;background:var(--pb-border);margin-left:12px;"></div></div>
            
            <div class="pb-grid" style="gap:16px;margin-bottom:16px;">
                <div class="gc-6">
                    <div class="pb-tile-hdr"><div><div class="pb-tile-hdr-title">Rendimiento Operativo</div><div class="pb-tile-hdr-sub">Agrupado por turno</div></div></div>
                    <div class="pb-widget">@livewire(\App\Filament\Widgets\ProductionByShiftChart::class)</div>
                </div>
                <div class="gc-6">
                    <div class="pb-tile-hdr"><div><div class="pb-tile-hdr-title">Materias Primas</div><div class="pb-tile-hdr-sub">Composición del stock</div></div></div>
                    <div class="pb-widget">@livewire(\App\Filament\Widgets\InventoryDistributionChart::class)</div>
                </div>
            </div>
            
            <div class="pb-grid">
                <div class="gc-12">
                    <div class="pb-tile-hdr"><div><div class="pb-tile-hdr-title">Dinamismo (Entradas vs Salidas)</div><div class="pb-tile-hdr-sub">Movimientos históricos</div></div></div>
                    <div class="pb-widget">@livewire(\App\Filament\Widgets\InventoryMovementsTrendChart::class)</div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
