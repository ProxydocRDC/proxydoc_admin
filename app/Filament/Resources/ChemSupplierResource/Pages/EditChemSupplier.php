<?php

namespace App\Filament\Resources\ChemSupplierResource\Pages;

use App\Filament\Resources\ChemSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemSupplier extends EditRecord
{
    protected static string $resource = ChemSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
