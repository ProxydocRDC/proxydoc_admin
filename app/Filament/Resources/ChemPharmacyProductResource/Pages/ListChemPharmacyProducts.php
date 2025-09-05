<?php

namespace App\Filament\Resources\ChemPharmacyProductResource\Pages;

use App\Filament\Resources\ChemPharmacyProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemPharmacyProducts extends ListRecords
{
    protected static string $resource = ChemPharmacyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un produit de pharmacie')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
