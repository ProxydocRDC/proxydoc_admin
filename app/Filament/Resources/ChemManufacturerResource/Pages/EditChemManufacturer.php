<?php

namespace App\Filament\Resources\ChemManufacturerResource\Pages;

use App\Filament\Resources\ChemManufacturerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemManufacturer extends EditRecord
{
    protected static string $resource = ChemManufacturerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
