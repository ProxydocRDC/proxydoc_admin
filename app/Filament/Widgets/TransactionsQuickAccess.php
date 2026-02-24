<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MainPaymentResource;
use App\Models\MainPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransactionsQuickAccess extends BaseWidget
{
    protected function getStats(): array
    {
        $url = MainPaymentResource::getUrl('index');
        $total = MainPayment::query()->count();
        $approved = MainPayment::where('payment_status', 2)->count();

        return [
            Stat::make('Transactions', (string) $total)
                ->description('Voir toutes les transactions')
                ->url($url)
                ->color('primary'),

            Stat::make('Approuvées', (string) $approved)
                ->description('Paiements validés')
                ->url($url . '?tableFilters[payment_status]=2')
                ->color('success'),
        ];
    }
}
