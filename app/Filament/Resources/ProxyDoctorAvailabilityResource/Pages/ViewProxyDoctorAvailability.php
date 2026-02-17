<?php
namespace App\Filament\Resources\ProxyDoctorAvailabilityResource\Pages;

use App\Filament\Resources\ProxyDoctorAvailabilityResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewProxyDoctorAvailability extends ViewRecord
{
    protected static string $resource = ProxyDoctorAvailabilityResource::class;

     protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
            Actions\EditAction::make(),
        ];
    }
}
