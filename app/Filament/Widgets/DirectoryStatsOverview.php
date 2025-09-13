<?php

namespace App\Filament\Widgets;

use App\Models\MainCity;
use App\Models\MainStatus;
use App\Models\MainCountry;
use App\Models\MainCurrency;
use App\Models\MainSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Card;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class DirectoryStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected ?string $heading = 'Référentiels';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getCards(): array
    {
        return [
            Card::make('Pays',          MainCountry::query()->count())->icon('heroicon-o-globe-alt'),
            Card::make('Villes',        MainCity::query()->count())->icon('heroicon-o-map-pin'),
            Card::make('Devises',       MainCurrency::query()->count())->icon('heroicon-o-banknotes'),
            Card::make('Statuts',       MainStatus::query()->count())->icon('heroicon-o-tag'),
             Card::make('Abonnements',   SubscriptionPlan::query()->count())->icon('heroicon-o-ticket'),
        ];
    }
}
