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
                    $v = time();
                    return new HtmlString("
                        <link rel=\"manifest\" href=\"/manifest.json\">
                        <link href=\"https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap\" rel=\"stylesheet\">
                        <link href=\"/css/dashboard.css?v={$v}\" rel=\"stylesheet\">
                        <script src=\"https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js\"></script>
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
