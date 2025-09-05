<?php

namespace App\Filament\Resources\ProxyAppointmentResource\Pages;

use App\Filament\Resources\ProxyAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyAppointments extends ListRecords
{
    protected static string $resource = ProxyAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
