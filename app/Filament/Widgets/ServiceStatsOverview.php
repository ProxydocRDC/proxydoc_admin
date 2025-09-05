<?php

namespace App\Filament\Widgets;

use App\Models\ProxyDoctor;
use App\Models\ProxyPatient;
use App\Models\ProxyService;
use Illuminate\Support\Carbon;
use App\Models\ProxyAppointment;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ServiceStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s'; // rafraîchit toutes les 60s (optionnel)
    protected ?string $heading = 'Aperçu général';

    public static function canView(): bool
    {
        // Ajuste selon Shield si besoin (ex: 'view_widget_service_stats_overview')
        return Auth::check();
    }

    protected function getCards(): array
    {
        $today = Carbon::today();

        return [
            Card::make('Services actifs', ProxyService::query()->where('status', 1)->count())
                ->icon('heroicon-o-briefcase')
                ->description('Total disponibles'),

            Card::make('Médecins actifs', ProxyDoctor::query()->where('status', 1)->count())
                ->icon('heroicon-o-user-group')
                ->description('Profils médecin'),

            Card::make('Patients', ProxyPatient::query()->count())
                ->icon('heroicon-o-user'),

            Card::make('RDV aujourd’hui', ProxyAppointment::query()
                ->whereDate('scheduled_at', $today)
                ->count())
                ->icon('heroicon-o-calendar')
                ->description($today->format('d/m')),
        ];
    }
}
