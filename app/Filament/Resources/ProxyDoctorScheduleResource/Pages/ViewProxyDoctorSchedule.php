<?php
// View
namespace App\Filament\Resources\ProxyDoctorScheduleResource\Pages;
use App\Filament\Resources\ProxyDoctorResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewProxyDoctorSchedule  extends ViewRecord
{
    protected static string $resource = ProxyDoctorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\EditAction::make(),
        ];
    }
}
