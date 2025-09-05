<?php

namespace App\Filament\Widgets;

use App\Models\ProxyService;
use Illuminate\Support\Carbon;
use App\Models\ProxyAppointment;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\BarChartWidget;
use Illuminate\Support\Facades\Auth;

class AppointmentsByServiceChart extends BarChartWidget
{
    protected static ?string $heading = 'RDV par service (30 jours)';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getData(): array
    {
        $from = Carbon::now()->subDays(30);

        $rows = ProxyAppointment::query()
            ->select('service_id', DB::raw('COUNT(*) as total'))
            ->where('scheduled_at', '>=', $from)
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->with('service:id,label')
            ->limit(12)
            ->get();

        $labels = $rows->map(fn ($r) => $r->service?->label ?? ('Service #'.$r->service_id))->all();
        $data   = $rows->pluck('total')->all();

        return [
            'datasets' => [
                [
                    'label' => 'RDV (30j)',
                    'data'  => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
