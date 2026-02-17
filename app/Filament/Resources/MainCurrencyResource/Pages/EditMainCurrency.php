<?php

namespace App\Filament\Resources\MainCurrencyResource\Pages;

use App\Filament\Resources\MainCurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainCurrency extends EditRecord
{
    protected static string $resource = MainCurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
