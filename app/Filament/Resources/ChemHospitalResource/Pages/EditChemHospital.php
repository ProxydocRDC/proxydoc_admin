<?php

namespace App\Filament\Resources\ChemHospitalResource\Pages;

use App\Filament\Resources\ChemHospitalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemHospital extends EditRecord
{
    protected static string $resource = ChemHospitalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
