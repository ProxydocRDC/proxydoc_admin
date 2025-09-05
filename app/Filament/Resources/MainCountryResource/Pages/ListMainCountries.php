<?php

namespace App\Filament\Resources\MainCountryResource\Pages;

use App\Filament\Resources\MainCountryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMainCountries extends ListRecords
{
    protected static string $resource = MainCountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un pays')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
