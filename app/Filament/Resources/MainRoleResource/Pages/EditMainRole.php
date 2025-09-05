<?php

namespace App\Filament\Resources\MainRoleResource\Pages;

use App\Filament\Resources\MainRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainRole extends EditRecord
{
    protected static string $resource = MainRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
