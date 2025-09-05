<?php

namespace App\Filament\Resources\ChemPosologyResource\Pages;

use App\Filament\Resources\ChemPosologyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemPosology extends EditRecord
{
    protected static string $resource = ChemPosologyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
