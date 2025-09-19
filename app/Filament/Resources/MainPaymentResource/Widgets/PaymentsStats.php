<?php

namespace App\Filament\Resources\MainPaymentResource\Widgets;

use App\Models\MainPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentsStats extends BaseWidget
{
    protected function getStats(): array
    {
        $from = now()->subDays(30);

        $totalUsd = (float) MainPayment::where('created_at', '>=', $from)
            ->where('currency', 'USD')->sum('amount');

        $totalCdf = (float) MainPayment::where('created_at', '>=', $from)
            ->where('currency', 'CDF')->sum('amount');

        $approved = MainPayment::where('created_at', '>=', $from)->where('payment_status', 2)->count();
        $pending  = MainPayment::where('created_at', '>=', $from)->where('payment_status', 1)->count();
        $rejected = MainPayment::where('created_at', '>=', $from)->where('payment_status', 3)->count();

        return [
            Stat::make('Payé (USD / 30 j)', number_format($totalUsd, 2, ',', ' ') . ' USD')
                ->description('Somme des montants payés')
                ->color('primary'),

            Stat::make('Payé (CDF / 30 j)', number_format($totalCdf, 2, ',', ' ') . ' CDF')
                ->description('Somme des montants payés')
                ->color('info'),

            Stat::make('Statuts (30 j)', "✓ {$approved} • ~ {$pending} • ✗ {$rejected}")
                ->description('Approuvés • En cours • Rejetés')
                ->color('gray'),
        ];
    }
}
