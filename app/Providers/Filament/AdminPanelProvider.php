<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Hasnayeen\Themes\ThemesPlugin;
use Hasnayeen\Themes\Http\Middleware\SetTheme;
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
                ThemesPlugin::make(),
                FilamentApexChartsPlugin::make(),
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn () => new \Illuminate\Support\HtmlString(
                    '<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">' .
                    '<link href="/css/dashboard.css?v=' . filemtime(public_path('css/dashboard.css')) . '" rel="stylesheet">' .
                    '<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js"></script>'
                )
            )

            
            ->login(\App\Filament\Pages\Auth\Login::class)

            ->colors([
                'primary' => Color::Lime,
                'success' => Color::Emerald,
                'info'    => Color::Blue,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'gray'    => Color::Slate,
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
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\GeneralReports::class,
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
                SetTheme::class,
            ])

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
