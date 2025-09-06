<?php

namespace App\Filament\Resources\MainZoneResource\Pages;

use App\Filament\Resources\MainZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMainZones extends ListRecords
{
    protected static string $resource = MainZoneResource::class;
    protected ?string $heading = 'Zones de livraison';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
