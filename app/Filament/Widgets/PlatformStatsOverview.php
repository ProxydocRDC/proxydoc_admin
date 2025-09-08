<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\ProxyDoctor;
use App\Models\ProxyPatient;
use App\Models\ProxyService;
use Illuminate\Support\Carbon;
use App\Models\ProxyAppointment;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Card;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class PlatformStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected ?string $heading = 'Aperçu plateforme';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getCards(): array
    {
        $today = Carbon::today();

        return [
            Card::make('Utilisateurs', User::query()->count())->icon('heroicon-o-users'),
            Card::make('Médecins actifs', ProxyDoctor::query()->where('status', 1)->count())->icon('heroicon-o-user-group'),
            Card::make('Patients', ProxyPatient::query()->count())->icon('heroicon-o-user'),
            Card::make('Services actifs', ProxyService::query()->where('status', 1)->count())->icon('heroicon-o-briefcase'),
            Card::make("RDV aujourd'hui", ProxyAppointment::query()->whereDate('scheduled_at', $today)->count())->icon('heroicon-o-calendar'),
        ];
    }
}
