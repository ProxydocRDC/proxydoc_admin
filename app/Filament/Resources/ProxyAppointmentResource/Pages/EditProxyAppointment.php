<?php

namespace App\Filament\Resources\ProxyAppointmentResource\Pages;

use App\Filament\Resources\ProxyAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyAppointment extends EditRecord
{
    protected static string $resource = ProxyAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
