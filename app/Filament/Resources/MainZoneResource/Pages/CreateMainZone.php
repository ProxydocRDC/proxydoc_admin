<?php

namespace App\Filament\Resources\MainZoneResource\Pages;

use App\Filament\Resources\MainZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMainZone extends CreateRecord
{
    protected static string $resource = MainZoneResource::class;
    protected ?string $heading = 'Créer une zone';
}
