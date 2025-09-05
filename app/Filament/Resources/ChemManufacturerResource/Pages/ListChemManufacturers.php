<?php

namespace App\Filament\Resources\ChemManufacturerResource\Pages;

use App\Filament\Resources\ChemManufacturerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemManufacturers extends ListRecords
{
    protected static string $resource = ChemManufacturerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un fabricant')
                ->icon('heroicon-o-building-office-2'),
        ];
    }
}
