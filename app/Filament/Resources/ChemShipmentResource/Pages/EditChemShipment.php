<?php

namespace App\Filament\Resources\ChemShipmentResource\Pages;

use App\Filament\Resources\ChemShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemShipment extends EditRecord
{
    protected static string $resource = ChemShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
