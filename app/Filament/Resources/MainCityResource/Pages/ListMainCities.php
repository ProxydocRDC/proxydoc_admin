<?php

namespace App\Filament\Resources\MainCityResource\Pages;

use App\Filament\Resources\MainCityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMainCities extends ListRecords
{
    protected static string $resource = MainCityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter une ville')
                ->icon('heroicon-o-map-pin'),
        ];
    }
}
