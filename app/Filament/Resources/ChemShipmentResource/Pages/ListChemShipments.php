<?php

namespace App\Filament\Resources\ChemShipmentResource\Pages;

use App\Filament\Resources\ChemShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemShipments extends ListRecords
{
    protected static string $resource = ChemShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle livraison')
            ->icon('heroicon-o-truck'),
        ];
    }
}
