<?php
// View
namespace App\Filament\Resources\ProxyAppointmentResource\Pages;
use App\Filament\Resources\ProxyAppointmentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
class ViewProxyAppointment extends ViewRecord
{
    protected static string $resource = ProxyAppointmentResource::class;

     protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
            Actions\EditAction::make(),
        ];
    }
}
