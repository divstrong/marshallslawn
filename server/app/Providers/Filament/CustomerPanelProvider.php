<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The customer-facing portal — a separate Filament panel on the 'customer'
 * auth guard (Customer model), completely isolated from the admin panel.
 * Customers authenticate here and never touch the admin 'web' guard.
 */
class CustomerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('portal')
            ->authGuard('customer')
            ->login()
            ->brandName("Marshall's Lawn")
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('4rem')
            ->colors([
                'primary' => [
                    50 => '#fef1f3',
                    100 => '#fde4e8',
                    200 => '#fbc8d1',
                    300 => '#f8a0af',
                    400 => '#f4657f',
                    500 => '#e00a35',
                    600 => '#c9092f',
                    700 => '#a80828',
                    800 => '#8b0721',
                    900 => '#73061c',
                    950 => '#40020e',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\Filament\Portal\Resources')
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\Filament\Portal\Pages')
            ->discoverWidgets(in: app_path('Filament/Portal/Widgets'), for: 'App\Filament\Portal\Widgets')
            ->pages([])
            ->widgets([
                \App\Filament\Portal\Widgets\CustomerStatsOverview::class,
            ])
            ->middleware([
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
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('
                    <style>
                        .fi-simple-layout {
                            background-image: url("' . asset('img/loginbg.png') . '");
                            background-size: cover;
                            background-position: center;
                            background-repeat: no-repeat;
                        }
                        .fi-btn-color-primary:hover {
                            --c-400: 0 0 0 !important;
                            --c-500: 0 0 0 !important;
                            --c-600: 0 0 0 !important;
                            background-color: #000 !important;
                        }
                    </style>
                '),
            );
    }
}
