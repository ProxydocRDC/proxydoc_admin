<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Filament\Resources\ParrainesResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ParrainesStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole([config('filament-shield.super_admin.name', 'super_admin'), 'Admin'])) {
            return true;
        }
        return $user->can('view_any_user');
    }

    protected function getStats(): array
    {
        $count = User::query()->whereNotNull('code_parrainage')->where('code_parrainage', '!=', '')->count();

        $url = ParrainesResource::getUrl('index');

        return [
            Stat::make('Utilisateurs parrainés', (string) $count)
                ->description('Inscrits avec un code parrainage')
                ->color('success')
                ->icon('heroicon-o-user-plus')
                ->url($url),
        ];
    }
}
