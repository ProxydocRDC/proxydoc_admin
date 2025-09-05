<?php

namespace App\Filament\Resources\MainUserResource\Pages;

use App\Filament\Resources\MainUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainUser extends EditRecord
{
    protected static string $resource = MainUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
