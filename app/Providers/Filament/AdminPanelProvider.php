<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\MainTenant;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\ProductsTrend;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Widgets\TopServicesTable;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\AppointmentsTrend;
use App\Filament\Widgets\CatalogStatsOverview;
use App\Filament\Widgets\ServiceStatsOverview;
use App\Filament\Widgets\PlatformStatsOverview;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Widgets\DirectoryStatsOverview;
use App\Filament\Widgets\LogisticsStatsOverview;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Widgets\AppointmentsByServiceChart;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Filament\Widgets\DoctorAvailabilityTodayTable;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
                ->colors([
                'danger'  => Color::Red,       // Pour garder la cohérence avec le rouge vif du stéthoscope et "DOC"
                'gray'    => Color::Gray,      // Neutre, inchangé
                'info'    => Color::Cyan,      // Pour correspondre au bleu clair/cyan du logo
                'primary' => Color::Sky,       // Bleu principal (pour "PROXY" et le téléphone)
                'success' => Color::Emerald,   // Tu peux garder un vert pour différencier les succès
                'warning' => Color::Amber,     // Jaune/orangé pour les avertissements
            ])
             ->passwordReset()
        ->emailVerification()
        ->profile(EditProfile::class)
        // ->profile(isSimple: false)
        // ->pages([
        //     EditProfile::class, // ✅ ta page profil sur-mesure
        // ])
            ->colors([
                'primary' => "#13A4D3",
                // 'primary' => Color::Amber,
            ])
            ->authGuard('web')
            ->unsavedChangesAlerts()
            ->brandName('Dashboard PROXYDOC')
            // ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(asset('assets/images/PROFI-TIK.jpg'))
            ->brandLogoHeight(fn() => Auth::check() ? '3rem' : '5rem')
            ->favicon(asset('assets/images/log.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                    // ServiceStatsOverview::class,
                    // AppointmentsByServiceChart::class,
                    // TopServicesTable::class,
                    // DoctorAvailabilityTodayTable::class,
                    //  PlatformStatsOverview::class,
                    // CatalogStatsOverview::class,
                    // LogisticsStatsOverview::class,
                    // DirectoryStatsOverview::class,

                    // AppointmentsByServiceChart::class,
                    // AppointmentsTrend::class,
                    // ProductsTrend::class,
                    // \App\Filament\Widgets\SupplierStatsOverview::class,

            // DoctorAvailabilityTodayTable::class,
            ])
            ->databaseNotifications()
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
                 EasyFooterPlugin::make()->withFooterPosition('footer')->withLoadTime('Cette page a été chargée dans')
                ->withLogo(asset('assets/images/PROFI-TIK.jpg'), 'https://proxydoc.org')
                ->withLinks([
                    ['title' => 'A propos', 'url' => 'https://proxydoc.org'],
                    ['title' => 'Privacy Policy', 'url' => 'https://proxydoc.org/privacy-policy']
                ])->withBorder(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
