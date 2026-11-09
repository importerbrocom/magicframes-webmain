<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Filament\Widgets\AccountWidget;
use App\Filament\Widgets\EnquiryStats;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()   // "Forgot password?" e-mail link (needs working MAIL_* settings)
            ->profile()         // every user can change their own name, e-mail and password
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => Blade::render(
                '<link rel="stylesheet" href="{{ asset(\'css/admin-typography.css\') }}?v={{ filemtime(public_path(\'css/admin-typography.css\')) }}" />'
            ))
            ->brandName('Magic Frames Studio')
            ->brandLogo(asset('images/admin-logo-dark.png'))          // light mode: dark logo
            ->darkModeBrandLogo(asset('images/admin-logo-light.png'))  // dark mode: white logo
            ->brandLogoHeight('3.75rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex('#C9A24A'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                EnquiryStats::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
