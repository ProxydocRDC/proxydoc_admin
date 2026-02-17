<?php

namespace App\Filament\Resources\MainCountryResource\Pages;

use App\Filament\Resources\MainCountryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainCountry extends EditRecord
{
    protected static string $resource = MainCountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
