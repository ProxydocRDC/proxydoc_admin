<?php

namespace App\Filament\Widgets;

use App\Models\proxy_categories;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ProxyCategoryStats extends BaseWidget
{
    protected ?string $heading = 'Catégories (Paramètres)';

    protected function getCards(): array
    {
        $total   = proxy_categories::count();
        $active  = proxy_categories::where('status', 1)->count();
        $inactive = $total - $active;

        return [
            Card::make('Total', $total)->icon('heroicon-o-tag'),
            Card::make('Actives', $active)->color('success')->description('Utilisables'),
            Card::make('Inactives', $inactive)->color('danger')->description('Masquées'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin','admin']) ?? false;
    }
}
