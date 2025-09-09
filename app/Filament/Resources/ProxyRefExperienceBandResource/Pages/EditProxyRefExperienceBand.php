<?php

namespace App\Filament\Resources\ProxyRefExperienceBandResource\Pages;

use App\Filament\Resources\ProxyRefExperienceBandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyRefExperienceBand extends EditRecord
{
    protected static string $resource = ProxyRefExperienceBandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
