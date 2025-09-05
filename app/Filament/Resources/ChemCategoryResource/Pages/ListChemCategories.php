<?php

namespace App\Filament\Resources\ChemCategoryResource\Pages;

use App\Filament\Resources\ChemCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemCategories extends ListRecords
{
    protected static string $resource = ChemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter une catégorie')
                ->icon('heroicon-o-building-storefront'),
        ];
    }
}
