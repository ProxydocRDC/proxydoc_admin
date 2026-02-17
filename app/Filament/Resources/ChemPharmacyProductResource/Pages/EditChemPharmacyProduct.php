<?php

namespace App\Filament\Resources\ChemPharmacyProductResource\Pages;

use App\Filament\Resources\ChemPharmacyProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemPharmacyProduct extends EditRecord
{
    protected static string $resource = ChemPharmacyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
