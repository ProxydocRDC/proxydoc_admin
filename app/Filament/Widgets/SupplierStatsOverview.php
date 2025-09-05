<?php

namespace App\Filament\Widgets;

use App\Models\ChemOrder;
use App\Models\ChemPharmacy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class SupplierStatsOverview extends BaseWidget
{
    protected ?string $heading = 'Mes statistiques';

    protected function getCards(): array
    {
        $u = Auth::user();
        $from = Carbon::now()->subDays(30);

        // ADMIN : tout voir
        if ($u?->hasRole('super_admin')) {
            $pharmacies = ChemPharmacy::count();
            $orders30   = ChemOrder::where('created_at', '>=', $from)->count();
            $revenue30  = (float) ChemOrder::where('created_at', '>=', $from)->sum('total_amount');
        } else {
            // FOURNISSEUR : seulement ses données
            $sid = $u?->supplier?->id ?? 0;

            $pharmacies = ChemPharmacy::where('supplier_id', $sid)->count();

            $orders30   = ChemOrder::forSupplier($sid)
                            ->where('created_at', '>=', $from)
                            ->count();

            $revenue30  = (float) ChemOrder::forSupplier($sid)
                            ->where('created_at', '>=', $from)
                            ->sum('total_amount');
        }

        return [
            Card::make('Mes pharmacies', $pharmacies)->icon('heroicon-o-building-storefront'),
            Card::make('Commandes (30j)', $orders30)->icon('heroicon-o-shopping-bag'),
            Card::make('CA (30j)', number_format($revenue30, 2, ',', ' ') . ' USD')->icon('heroicon-o-banknotes'),
        ];
    }

    public static function canView(): bool
    {
        // Ajuste aux rôles de ton app
        return Auth::user()?->hasAnyRole(['super_admin','fournisseur']) ?? false;
    }
}
