<?php

namespace App\Providers\Filament;

use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
// use Hasnayeen\Themes\ThemesPlugin;
// use Hasnayeen\Themes\Http\Middleware\SetTheme;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugins([
                // ThemesPlugin::make(),
                FilamentApexChartsPlugin::make(),
            ])
            ->maxContentWidth(null) // ✅ Forzar ancho total en todo el panel
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function () {
                    $settings = Cache::remember('appearance_settings', 1800, function () {
                        return Setting::whereIn('key', [
                            'primary_color_1',
                            'primary_color_2',
                            'sidebar_width',
                            'border_radius',
                            'glass_effect',
                            'mesh_background',
                            'button_layout',
                            'shadow_depth',
                            'bg_color',
                            'sidebar_bg_color',
                            'card_bg_color',
                            'text_color',
                        ])->pluck('value', 'key')->toArray();
                    });

                    $p1 = $settings['primary_color_1'] ?? '#6366f1';
                    $p2 = $settings['primary_color_2'] ?? '#3b82f6';
                    $sw = ($settings['sidebar_width'] ?? 14).'rem';
                    $br = ($settings['border_radius'] ?? 12).'px';

                    $isGlass = (bool) ($settings['glass_effect'] ?? true);
                    $btnStyle = $settings['button_layout'] ?? 'pill';
                    $shadowDepth = $settings['shadow_depth'] ?? 'medium';

                    $bgColor = $settings['bg_color'] ?? '#f8fafc';
                    $sidebarBg = $settings['sidebar_bg_color'] ?? '#ffffff';
                    $cardBg = $settings['card_bg_color'] ?? '#ffffff';
                    $textColor = $settings['text_color'] ?? '#1e293b';

                    $shadows = [
                        'none' => 'none',
                        'soft' => '0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05)',
                        'medium' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
                        'deep' => '0 25px 50px -12px rgb(0 0 0 / 0.15)',
                    ];
                    $selectedShadow = $shadows[$shadowDepth] ?? $shadows['medium'];

                    $btnRadius = match ($btnStyle) {
                        'square' => '2px',
                        'pill' => '9999px',
                        default => $br,
                    };

                    // Use a static version string or a more reliable cache for the file time
                    $v = '1.0.0';
                    try {
                        $path = public_path('css/dashboard.css');
                        if (file_exists($path)) {
                            $v = filemtime($path);
                        }
                    } catch (\Exception $e) {
                    }

                    $logoUrl = asset('images/logo-perfloplast-premium.png');

                    $styles = "
                        <style>
                            :root {
                                --p-1: {$p1};
                                --p-2: {$p2};
                                --fi-sidebar-width: {$sw};
                                --fi-main-content-max-width: 100%;
                                --premium-shadow: {$selectedShadow};
                                --btn-radius: {$btnRadius};
                                --border-radius: {$br};
                                --bg-color: {$bgColor};
                                --sidebar-bg: {$sidebarBg};
                                --card-bg: {$cardBg};
                                --text-primary: {$textColor};
                                --text-muted: color-mix(in srgb, var(--text-primary), transparent 40%);
                                --border-color: rgba(0,0,0,0.08);
                                --main-gradient: linear-gradient(135deg, var(--p-1), var(--p-2));
                            }

                            .dark {
                                --bg-color: #040609;
                                --sidebar-bg: #090e16;
                                --card-bg: #0e1420;
                                --text-primary: #f8fafc;
                                --border-color: rgba(255,255,255,0.04);
                                --premium-shadow: 0 30px 60px -12px rgba(0,0,0,0.7);
                            }
                            
                            body {
                                background-color: var(--bg-color) !important;
                                background-image: 
                                    radial-gradient(at 0% 0%, color-mix(in srgb, var(--p-1), transparent 70%) 0, transparent 60%), 
                                    radial-gradient(at 100% 0%, color-mix(in srgb, var(--p-2), transparent 70%) 0, transparent 60%),
                                    linear-gradient(180deg, 
                                        var(--bg-color) 0%, 
                                        color-mix(in srgb, var(--bg-color), var(--p-1) 25%) 40%, 
                                        color-mix(in srgb, var(--bg-color), var(--p-2) 80%) 100%
                                    ) !important;
                                background-attachment: fixed !important;
                                color: var(--text-primary) !important;
                                transition: background-color 0.8s ease;
                                min-height: 100vh;
                                font-family: 'Outfit', sans-serif !important;
                                overflow-x: clip; /* Usar clip en lugar de hidden para evitar problemas con sticky/fixed */
                            }

                            .dark body {
                                background-image: 
                                    radial-gradient(at 0% 0%, color-mix(in srgb, var(--p-1), transparent 60%) 0, transparent 50%), 
                                    radial-gradient(at 100% 0%, color-mix(in srgb, var(--p-2), transparent 60%) 0, transparent 50%),
                                    linear-gradient(180deg, 
                                        #040609 0%, 
                                        color-mix(in srgb, #040609, var(--p-1) 30%) 50%, 
                                        color-mix(in srgb, #040609, var(--p-2) 90%) 100%
                                    ) !important;
                            }
                            
                            .fi-layout, .fi-main { background: transparent !important; }

                            .fi-topbar, .fi-sidebar-item-active, .fi-btn-color-primary {
                                background: var(--main-gradient) !important;
                                color: #ffffff !important;
                                border: none !important;
                            }
                            
                            .fi-sidebar-item-active, .fi-btn-color-primary {
                                box-shadow: 0 4px 15px -1px color-mix(in srgb, var(--p-1), transparent 60%) !important;
                            }

                            .fi-topbar {
                                height: 5.5rem !important;
                                border-bottom: none !important;
                                backdrop-filter: blur(20px) !important;
                                -webkit-backdrop-filter: blur(20px) !important;
                                background-color: color-mix(in srgb, var(--p-1), transparent 85%) !important;
                            }

                            .fi-card, .fi-section, .fi-sidebar {
                                border-radius: var(--border-radius) !important;
                                border: 1px solid var(--border-color) !important;
                                overflow: visible !important; /* Esencial para que los dropdowns no se corten */
                            }

                            ".($isGlass ? '
                            .fi-card, .fi-section {
                                background-color: color-mix(in srgb, var(--card-bg), transparent 15%) !important;
                                /* Retirado backdrop-filter aquí para evitar bugs de superposición de menú (z-index traps) */
                            }
                            .fi-sidebar {
                                background-color: transparent !important;
                                backdrop-filter: blur(10px) !important;
                            }
                            ' : '
                            .fi-sidebar { background-color: var(--sidebar-bg) !important; }
                            .fi-card, .fi-section { background-color: var(--card-bg) !important; }
                            ')."

                            .fi-card, .fi-section { box-shadow: var(--premium-shadow) !important; }

                            @media (min-width: 1024px) {
                                .fi-sidebar {
                                    position: fixed !important;
                                    width: var(--fi-sidebar-width) !important;
                                }
                                .fi-main-ctn {
                                    margin-inline-start: var(--fi-sidebar-width) !important;
                                    width: calc(100vw - var(--fi-sidebar-width)) !important;
                                }
                                .fi-main { width: 100% !important; padding: 2rem !important; }
                                .fi-main > div { max-width: none !important; width: 100% !important; }
                            }

                            .fi-header-heading { font-weight: 800 !important; }
                            .fi-sidebar-group-label { font-weight: 700 !important; color: var(--p-1) !important; }

                    /* Optimización para el Logo en Sidebar */
                    .fi-sidebar-header {
                        height: 6.5rem !important; /* Aumentado ligeramente para dar aire */
                        display: flex;
                        align-items: center;
                        justify-content: flex-start !important;
                        padding-left: 1.5rem !important; 
                        padding-right: 1.5rem;
                        margin-bottom: 0.5rem; /* Separación con el primer item */
                    }

                    .fi-sidebar-header > div, 
                    .fi-sidebar-header > a {
                        width: 100%;
                        display: flex;
                        align-items: center;
                    }

                    .fi-sidebar-header .fi-logo-container,
                    .fi-sidebar-header a > div {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                        padding: 0.25rem;
                        border-radius: var(--border-radius, 12px);
                        transition: all 0.2s ease;
                    }

                    .fi-logo {
                        height: auto !important;
                        max-height: 3.5rem !important; /* Reducido para que no pise los items */
                        width: auto !important;
                        object-fit: contain;
                        image-rendering: auto;
                        -webkit-font-smoothing: antialiased;
                        -moz-osx-font-smoothing: grayscale;
                        transform: translateZ(0);
                        margin-left: 0.5rem;
                    }

                    /* FIX CRITICO PARA DROPDOWNS */
                    .fi-dropdown-panel, 
                    .tippy-box, 
                    [data-tippy-root] {
                        z-index: 99999 !important;
                    }

                    .fi-main-ctn {
                        overflow: visible !important;
                    }
                    
                    .fi-main {
                        overflow: visible !important;
                    }
                        
                        /* MODO CLARO: Darken elimina el fondo gris claro permitiendo que el logo se funda con el sidebar */
                        mix-blend-mode: darken;
                        filter: none;
                    }
                    
                    /* ESTILO PARA MODO OSCURO */
                    html.dark .fi-logo {
                        /* Inversión total: fondo gris pasa a negro, letras claras relucen en neón */
                        mix-blend-mode: screen;
                        filter: invert(1) hue-rotate(180deg) brightness(1.2) contrast(1.1);
                    }




                            .group\/logo:hover svg {
                                filter: drop-shadow(0 0 8px var(--p-1));
                                transform: translateY(-2px);
                            }

                            /* Refinamiento de la página de Login */
                            .fi-simple-layout {
                                display: flex !important;
                                align-items: center !important;
                                justify-content: center !important;
                                min-height: 100vh !important;
                            }

                            .fi-simple-main {
                                background-color: color-mix(in srgb, var(--card-bg), transparent 10%) !important;
                                backdrop-filter: blur(24px) !important;
                                -webkit-backdrop-filter: blur(24px) !important;
                                border: 1px solid var(--border-color) !important;
                                border-radius: 2rem !important;
                                box-shadow: var(--premium-shadow) !important;
                                padding: 3rem !important;
                                max-width: 28rem !important;
                            }
                        </style>
                    ";

                    return new HtmlString("
                        <link href=\"https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap\" rel=\"stylesheet\">
                        <link href=\"/css/dashboard.css?v={$v}\" rel=\"stylesheet\">
                        <script src=\"https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js\"></script>
                        {$styles}
                    ");
                }
            )

            ->login(\App\Filament\Pages\Auth\Login::class)

            ->brandLogo(asset('images/logo-perfloplast-premium.png'))
            ->brandLogoHeight('5rem')
            ->brandName('PERFLOPLAST')
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn () => view('filament.logo')
            )

            ->colors([
                'primary' => Color::Lime,
                'success' => Color::Emerald,
                'info' => Color::Blue,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
            ])

            ->navigationGroups([
                NavigationGroup::make()->label('Escritorio'),
                NavigationGroup::make()->label('Operación'),
                NavigationGroup::make()->label('Catálogos'),
                NavigationGroup::make()->label('Usuarios y Roles'),
                NavigationGroup::make()->label('Bitácoras y Auditoría'),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Las páginas se descubren automáticamente en app/Filament/Pages
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            ->middleware([
                \App\Http\Middleware\EnsureUserIsActive::class,

                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,

                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,

                // ✅ OBLIGATORIO para que el plugin aplique el tema y aparezca bien
                // SetTheme::class,
            ])

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
