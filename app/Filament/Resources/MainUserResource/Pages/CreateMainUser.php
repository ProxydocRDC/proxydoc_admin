<?php

namespace App\Filament\Resources\MainUserResource\Pages;

use App\Filament\Resources\MainUserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMainUser extends CreateRecord
{
    protected static string $resource = MainUserResource::class;
}
