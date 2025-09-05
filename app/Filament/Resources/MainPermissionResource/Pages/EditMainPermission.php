<?php

namespace App\Filament\Resources\MainPermissionResource\Pages;

use App\Filament\Resources\MainPermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainPermission extends EditRecord
{
    protected static string $resource = MainPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
