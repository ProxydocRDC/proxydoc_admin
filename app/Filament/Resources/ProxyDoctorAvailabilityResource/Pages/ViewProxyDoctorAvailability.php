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
            Actions\DeleteAction::make(),
            Actions\EditAction::make(),
        ];
    }
}
