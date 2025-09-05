<?php

namespace App\Filament\Widgets;

use App\Models\ChemProduct;
use App\Models\ChemCategory;
use App\Models\ChemManufacturer;
use App\Models\ChemPharmacyProduct;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class CatalogStatsOverview extends BaseWidget
{
    protected ?string $heading = 'Catalogue';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getCards(): array
    {
        return [
            Card::make('Produits',         ChemProduct::query()->count())->icon('heroicon-o-cube'),
            Card::make('Catégories',       ChemCategory::query()->count())->icon('heroicon-o-rectangle-stack'),
            Card::make('Fabricants',       ChemManufacturer::query()->count())->icon('heroicon-o-building-storefront'),
            Card::make('Pharm. Produits',  ChemPharmacyProduct::query()->count())->icon('heroicon-o-beaker'),
        ];
    }
}
