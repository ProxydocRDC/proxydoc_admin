<?php
namespace App\Filament\Widgets;

use App\Models\UserSubscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ExpiringSubscriptionsStats extends BaseWidget
{
    protected function getCards(): array
    {
        $count = UserSubscription::expiringWithin(5)->count();

        return [
            Card::make('Abonnements ≤ 5 jours', (string) $count)
                ->description('Encore actifs')
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
