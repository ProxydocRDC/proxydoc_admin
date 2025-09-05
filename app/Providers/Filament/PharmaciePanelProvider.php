<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PharmaciePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pharmacie')
            ->path('pharmacie')
            ->login()
                ->colors([
                'danger'  => Color::Red,       // Pour garder la cohérence avec le rouge vif du stéthoscope et "DOC"
                'gray'    => Color::Gray,      // Neutre, inchangé
                'info'    => Color::Cyan,      // Pour correspondre au bleu clair/cyan du logo
                'primary' => Color::Sky,       // Bleu principal (pour "PROXY" et le téléphone)
                'success' => Color::Emerald,   // Tu peux garder un vert pour différencier les succès
                'warning' => Color::Amber,     // Jaune/orangé pour les avertissements
            ])
            ->colors([
                'primary' => "#13A4D3",
                // 'primary' => Color::Amber,
            ])
            ->brandName('Dashboard PROXYDOC')
            // ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(asset('assets/images/PROFI-TIK.jpg'))
            ->brandLogoHeight(fn() => auth()->check() ? '3rem' : '5rem')
            ->favicon(asset('assets/images/log.png'))

            ->discoverResources(in: app_path('Filament/Pharmacie/Resources'), for: 'App\\Filament\\Pharmacie\\Resources')
            ->discoverPages(in: app_path('Filament/Pharmacie/Pages'), for: 'App\\Filament\\Pharmacie\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Pharmacie/Widgets'), for: 'App\\Filament\\Pharmacie\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
