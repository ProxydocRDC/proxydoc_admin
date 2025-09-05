<?php

namespace App\Filament\Resources\ChemSupplierResource\Pages;

use App\Filament\Resources\ChemSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemSuppliers extends ListRecords
{
    protected static string $resource = ChemSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un fournisseur')
                ->icon('heroicon-o-truck'),
        ];
    }
}
