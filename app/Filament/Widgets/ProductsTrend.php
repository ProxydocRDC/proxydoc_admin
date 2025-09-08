<?php

namespace App\Filament\Widgets;

use App\Models\ChemProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\LineChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ProductsTrend extends LineChartWidget
{
    use HasWidgetShield;

      protected static ?string $heading = 'Nouveaux produits (14 jours)'; // <-- static ✅
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn ($d) => Carbon::today()->subDays($d));
        $labels = $days->map->format('d/m')->all();

        $data = $days->map(function (Carbon $day) {
            return ChemProduct::query()
                ->whereDate('created_at', $day)->count();
        })->all();

        return [
            'datasets' => [['label' => 'Produits', 'data' => $data]],
            'labels' => $labels,
        ];
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}
