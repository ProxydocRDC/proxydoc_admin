<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $from = now()->subWeek()->startOfDay();
        $to   = now()->endOfDay();

        $queryNew = User::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to);

        $total    = (clone $queryNew)->count();
        $termines = (clone $queryNew)->where('status', '!=', 5)->count();
        $enCours  = $total - $termines;

        $urlBase = UserResource::getUrl('index');

        return [
            Stat::make('Inscriptions (7 derniers jours)', (string) $total)
                ->description('Nouveaux comptes créés')
                ->color('primary')
                ->url($urlBase . '?tableFilters[created_at][created_from]=' . $from->format('Y-m-d') . '&tableFilters[created_at][created_to]=' . $to->format('Y-m-d')),

            Stat::make('Processus terminé', (string) $termines)
                ->description('Téléphone vérifié par OTP')
                ->color('success')
                ->url($urlBase . '?tableFilters[processus][value]=termine'),

            Stat::make('En cours', (string) $enCours)
                ->description('OTP à valider')
                ->color('warning')
                ->url($urlBase . '?tableFilters[processus][value]=en_cours'),
        ];
    }
}
