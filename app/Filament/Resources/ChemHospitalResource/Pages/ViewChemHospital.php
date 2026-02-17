<?php
namespace App\Filament\Resources\ChemHospitalResource\Pages;


use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ChemHospitalResource;

class ViewChemHospital extends ViewRecord
{
    protected static string $resource = ChemHospitalResource::class;

     protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
            Actions\EditAction::make(),
        ];
    }
}
