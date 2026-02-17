<?php

namespace App\Filament\Resources\MainZoneResource\Pages;

use App\Filament\Resources\MainZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainZone extends EditRecord
{
    protected static string $resource = MainZoneResource::class;
    protected ?string $heading = 'Modifier la zone';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
