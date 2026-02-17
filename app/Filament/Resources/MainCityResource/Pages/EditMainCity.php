<?php

namespace App\Filament\Resources\MainCityResource\Pages;

use App\Filament\Resources\MainCityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainCity extends EditRecord
{
    protected static string $resource = MainCityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
