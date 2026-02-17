<?php
namespace App\Filament\Resources\MainZoneResource\Pages;


use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\MainZoneResource;
use App\Filament\Resources\ChemHospitalResource;

class ViewMainZone extends ViewRecord
{
    protected static string $resource = MainZoneResource::class;

     protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
            Actions\EditAction::make(),
        ];
    }
}
