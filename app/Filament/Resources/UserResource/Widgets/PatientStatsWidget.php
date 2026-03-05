<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Filament\Resources\ProxyPatientResource;
use App\Models\ProxyPatient;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PatientStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole([config('filament-shield.super_admin.name', 'super_admin'), 'Admin'])) {
            return true;
        }
        return $user->can('widget_PatientStatsWidget');
    }

    protected function getStats(): array
    {
        $from = now()->subDays(3)->startOfDay();
        $to = now()->endOfDay();

        $count = ProxyPatient::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->count();

        $url = ProxyPatientResource::getUrl('index')
            . '?tableFilters[created_at][created_from]=' . $from->format('Y-m-d')
            . '&tableFilters[created_at][created_to]=' . $to->format('Y-m-d');

        return [
            Stat::make('Patients ajoutés (3 derniers jours)', (string) $count)
                ->description('Nouveaux patients créés par les utilisateurs')
                ->color('success')
                ->icon('heroicon-o-user-plus')
                ->url($url),
        ];
    }
}
