<?php

namespace App\Filament\Resources\ChemPharmaceuticalFormResource\Pages;

use App\Filament\Resources\ChemPharmaceuticalFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemPharmaceuticalForms extends ListRecords
{
    protected static string $resource = ChemPharmaceuticalFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
