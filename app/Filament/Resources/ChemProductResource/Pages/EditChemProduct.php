<?php

namespace App\Filament\Resources\ChemProductResource\Pages;

use App\Filament\Resources\ChemProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemProduct extends EditRecord
{
    protected static string $resource = ChemProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
