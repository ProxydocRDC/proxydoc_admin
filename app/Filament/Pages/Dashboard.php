<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
{
    return [
        \App\Filament\Resources\MainPaymentResource\Widgets\PaymentsStats::class,
        \App\Filament\Resources\MainPaymentResource\Widgets\PaymentsByMethodChart::class,
        \App\Filament\Resources\MainPaymentResource\Widgets\PaidByCurrencyTable::class, // plein écran
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
