<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Carbon;
use App\Models\ProxyAppointment;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\LineChartWidget;

class AppointmentsTrend extends LineChartWidget
{

      protected static ?string $heading = 'Nouveaux RDV (14 jours)'; // <-- static ✅
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn ($d) => Carbon::today()->subDays($d));
        $labels = $days->map->format('d/m')->all();

        $data = $days->map(function (Carbon $day) {
            return ProxyAppointment::query()
                ->whereDate('created_at', $day)->count();
        })->all();

        return [
            'datasets' => [['label' => 'RDV', 'data' => $data]],
            'labels' => $labels,
        ];
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}
