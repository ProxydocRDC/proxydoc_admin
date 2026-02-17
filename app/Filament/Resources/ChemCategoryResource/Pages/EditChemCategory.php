<?php

namespace App\Filament\Resources\ChemCategoryResource\Pages;

use App\Filament\Resources\ChemCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChemCategory extends EditRecord
{
    protected static string $resource = ChemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
