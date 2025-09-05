<?php

namespace App\Filament\Resources\MainStatusResource\Pages;

use App\Filament\Resources\MainStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainStatus extends EditRecord
{
    protected static string $resource = MainStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
