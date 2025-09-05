<?php

namespace App\Filament\Resources\ChemShipmentEventResource\Pages;

use App\Filament\Resources\ChemShipmentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemShipmentEvents extends ListRecords
{
    protected static string $resource = ChemShipmentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
