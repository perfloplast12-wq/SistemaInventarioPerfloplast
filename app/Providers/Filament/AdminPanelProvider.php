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
            ->spa() 
            ->databaseNotifications() 
            ->databaseNotificationsPolling('60s')
            ->maxContentWidth(null) 
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function () {
                    $settings = Cache::remember('appearance_settings', 3600, function () {
                        return Setting::whereIn('key', [
                            'primary_color_1',
                            'primary_color_2',
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
                    $sw = '16rem';
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

                    $v = Cache::remember('css_version', 3600, function() {
                        try {
                            $path = public_path('css/dashboard.css');
                            return file_exists($path) ? filemtime($path) : '1.0.0';
                        } catch (\Exception $e) { return '1.0.0'; }
                    });

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
                                --is-glass: ".($isGlass ? '1' : '0').";
                            }
                        </style>
                    ";

                    return new HtmlString("
                        <link rel=\"manifest\" href=\"/manifest.json\">
                        <meta name=\"theme-color\" content=\"{$p1}\">
                        <link href=\"https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap\" rel=\"stylesheet\">
                        <link href=\"/css/dashboard.css?v={$v}\" rel=\"stylesheet\">
                        <script src=\"https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js\"></script>
                        <script>
                            // Service worker disabled for stability
                        </script>
                        {$styles}
                    ");
                }
            )

            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('components.silent-tracker')->render(),
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
                'primary' => Color::Indigo,
                'success' => Color::Emerald,
                'info' => Color::Blue,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
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
            ])

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
