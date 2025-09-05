<?php

namespace App\Filament\Resources\ChemOrderResource\Pages;

use App\Filament\Resources\ChemOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemOrder extends EditRecord
{
    protected static string $resource = ChemOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
