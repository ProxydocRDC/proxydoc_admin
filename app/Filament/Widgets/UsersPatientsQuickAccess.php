<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\UserPatientResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersPatientsQuickAccess extends BaseWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole([config('filament-shield.super_admin.name', 'super_admin'), 'Admin'])) {
            return true;
        }
        return $user->can('view_any_user::patient');
    }

    protected function getStats(): array
    {
        $url = UserPatientResource::getUrl('index');
        $totalUsers = User::query()->count();
        $usersWithPatient = User::query()
            ->whereHas('patient', fn ($q) => $q->where('relation', 'self'))
            ->count();
        $usersWithoutPatient = $totalUsers - $usersWithPatient;

        return [
            Stat::make('Utilisateurs & Patients', (string) $totalUsers)
                ->description('Voir la liste')
                ->url($url)
                ->color('primary')
                ->icon('heroicon-o-user-group'),

            Stat::make('Avec fiche patient', (string) $usersWithPatient)
                ->description('Utilisateurs ayant une fiche patient')
                ->url($url . '?tableFilters[has_patient][value]=yes')
                ->color('success'),

            Stat::make('Sans fiche patient', (string) $usersWithoutPatient)
                ->description('Utilisateurs sans fiche patient')
                ->url($url . '?tableFilters[has_patient][value]=no')
                ->color('warning'),
        ];
    }
}
