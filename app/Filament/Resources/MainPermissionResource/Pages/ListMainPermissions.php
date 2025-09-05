<?php

namespace App\Filament\Resources\MainPermissionResource\Pages;

use App\Filament\Resources\MainPermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMainPermissions extends ListRecords
{
    protected static string $resource = MainPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
