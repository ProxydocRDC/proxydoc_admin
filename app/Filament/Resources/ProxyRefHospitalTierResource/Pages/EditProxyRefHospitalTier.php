<?php

namespace App\Filament\Resources\ProxyRefHospitalTierResource\Pages;

use App\Filament\Resources\ProxyRefHospitalTierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyRefHospitalTier extends EditRecord
{
    protected static string $resource = ProxyRefHospitalTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
