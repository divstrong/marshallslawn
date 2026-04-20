<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('4rem')
            ->sidebarCollapsibleOnDesktop()
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
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
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
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => new HtmlString('
                    <a href="/mobile" target="_blank" class="text-sm font-medium text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 transition">
                        Mobile View
                    </a>
                '),
            )
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
                        .fi-fo-file-upload .filepond--root {
                            cursor: pointer;
                            border: 2px dashed rgb(209 213 219);
                            border-radius: 0.5rem;
                            background-color: rgb(249 250 251);
                            transition: border-color 150ms ease, background-color 150ms ease;
                        }
                        .fi-fo-file-upload .filepond--root:hover {
                            border-color: #e00a35;
                            background-color: #fef1f3;
                        }
                        .dark .fi-fo-file-upload .filepond--root {
                            border-color: rgb(75 85 99);
                            background-color: rgba(255, 255, 255, 0.02);
                        }
                        .dark .fi-fo-file-upload .filepond--root:hover {
                            border-color: #f4657f;
                            background-color: rgba(224, 10, 53, 0.08);
                        }
                        .fi-fo-file-upload .filepond--label-action {
                            text-decoration: underline;
                            text-decoration-thickness: 1px;
                            text-underline-offset: 2px;
                            color: #e00a35;
                            font-weight: 500;
                        }
                        .fi-fo-file-upload .filepond--root:hover .filepond--label-action {
                            color: #a80828;
                        }
                        .dark .fi-fo-file-upload .filepond--label-action {
                            color: #f4657f;
                        }
                    </style>
                '),
            );
    }
}
