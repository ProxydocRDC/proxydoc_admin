<?php

namespace App\Filament\Resources\ChemPharmacyResource\Pages;

use App\Filament\Resources\ChemPharmacyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemPharmacies extends ListRecords
{
    protected static string $resource = ChemPharmacyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter une pharmacie')
                ->icon('heroicon-o-building-storefront'),
        ];
    }
}
