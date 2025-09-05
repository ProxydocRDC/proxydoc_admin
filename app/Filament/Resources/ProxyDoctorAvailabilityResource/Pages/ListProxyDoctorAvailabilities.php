<?php

namespace App\Filament\Resources\ProxyDoctorAvailabilityResource\Pages;

use App\Filament\Resources\ProxyDoctorAvailabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyDoctorAvailabilities extends ListRecords
{
    protected static string $resource = ProxyDoctorAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
