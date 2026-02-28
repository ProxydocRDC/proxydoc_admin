<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Session;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        parent::mount();

        if ($message = Session::pull('error')) {
            Notification::make()
                ->title('Accès refusé')
                ->body($message)
                ->danger()
                ->send();
        }
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\TransactionsQuickAccess::class,
            \App\Filament\Widgets\WeeklyRegistrationsTable::class,
            // Statistiques
            // \App\Filament\Widgets\ServiceStatsOverview::class,
            // \App\Filament\Widgets\CatalogStatsOverview::class,
            // \App\Filament\Widgets\DirectoryStatsOverview::class,
            // \App\Filament\Widgets\LogisticsStatsOverview::class,
            // \App\Filament\Widgets\PlatformStatsOverview::class,
            // \App\Filament\Widgets\SupplierStatsOverview::class,
            // \App\Filament\Widgets\ProxyCategoryStats::class,
            // \App\Filament\Widgets\ExpiringSubscriptionsStats::class,

            // // Graphiques
            // \App\Filament\Widgets\AppointmentsTrend::class,
            // \App\Filament\Widgets\AppointmentsByServiceChart::class,
            // \App\Filament\Widgets\ProductsTrend::class,

            // // Tableaux
            // \App\Filament\Widgets\TopServicesTable::class,
            // \App\Filament\Widgets\DoctorAvailabilityTodayTable::class,
            // \App\Filament\Widgets\ExpiringSubscriptionsTable::class,
            // \App\Filament\Widgets\ProductEncodersTable::class,

            // // Carte
            // \App\Filament\Widgets\ShipmentMapWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
