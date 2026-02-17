<?php

namespace App\Filament\Resources\ChemPharmacyResource\Pages;

use App\Filament\Resources\ChemPharmacyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemPharmacy extends EditRecord
{
    protected static string $resource = ChemPharmacyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
