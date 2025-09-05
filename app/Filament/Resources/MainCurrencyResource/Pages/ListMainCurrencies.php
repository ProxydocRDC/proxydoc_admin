<?php

namespace App\Filament\Resources\MainCurrencyResource\Pages;

use App\Filament\Resources\MainCurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMainCurrencies extends ListRecords
{
    protected static string $resource = MainCurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Nouvelle monnaie')   // <- Ton texte personnalisé
            ->icon('heroicon-o-banknotes'),
        ];
    }
}
