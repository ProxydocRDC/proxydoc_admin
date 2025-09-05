<?php

namespace App\Filament\Resources\ChemOrderResource\Pages;

use App\Filament\Resources\ChemOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemOrders extends ListRecords
{
    protected static string $resource = ChemOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
