<?php

namespace App\Filament\Resources\MainPaymentResource\Widgets;

use App\Models\MainPayment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PaymentsByMethodChart extends ChartWidget
{
    protected static ?string $heading = 'Montant payé par jour (30 jours)';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $from = now()->subDays(30)->startOfDay();

        $days = collect(range(0, 29))
            ->map(fn($i) => $from->copy()->addDays($i)->format('Y-m-d'))
            ->values();

        $dataMobile = [];
        $dataCard   = [];

        foreach ($days as $d) {
            $dataMobile[] = (float) MainPayment::whereDate('created_at', $d)
                ->where('method', MainPayment::METHOD_MOBILE_MONEY)
                ->sum('amount');
            $dataCard[]   = (float) MainPayment::whereDate('created_at', $d)
                ->where('method', MainPayment::METHOD_CARD)
                ->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Mobile Money',
                    'data'  => $dataMobile,
                ],
                [
                    'label' => 'Carte',
                    'data'  => $dataCard,
                ],
            ],
            'labels'   => $days->map(fn($d) => substr($d, 5))->all(), // MM-DD
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
