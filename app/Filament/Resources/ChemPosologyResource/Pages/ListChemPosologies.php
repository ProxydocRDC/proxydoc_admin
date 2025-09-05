<?php

namespace App\Filament\Resources\ChemPosologyResource\Pages;

use App\Filament\Resources\ChemPosologyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChemPosologies extends ListRecords
{
    protected static string $resource = ChemPosologyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter une posologie')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
