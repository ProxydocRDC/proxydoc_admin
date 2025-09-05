<?php

namespace App\Filament\Resources\ChemHospitalResource\Pages;

use App\Filament\Resources\ChemHospitalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemHospitals extends ListRecords
{
    protected static string $resource = ChemHospitalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
