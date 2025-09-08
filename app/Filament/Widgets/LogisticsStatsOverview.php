<?php

namespace App\Filament\Widgets;

use App\Models\ChemShipment;
use App\Models\ChemShipmentEvent;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Card;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class LogisticsStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected ?string $heading = 'Logistique';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getCards(): array
    {
        return [
            Card::make('Expéditions', ChemShipment::query()->count())->icon('heroicon-o-truck'),
            Card::make('Événements',  ChemShipmentEvent::query()->count())->icon('heroicon-o-clock'),
        ];
    }
}
