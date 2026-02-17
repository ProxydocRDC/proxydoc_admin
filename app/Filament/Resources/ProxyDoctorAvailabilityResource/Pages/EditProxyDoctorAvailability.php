<?php

namespace App\Filament\Resources\ProxyDoctorAvailabilityResource\Pages;

use App\Filament\Resources\ProxyDoctorAvailabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyDoctorAvailability extends EditRecord
{
    protected static string $resource = ProxyDoctorAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
