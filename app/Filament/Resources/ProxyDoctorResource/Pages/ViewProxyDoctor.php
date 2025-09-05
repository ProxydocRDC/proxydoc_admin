<?php
// View
namespace App\Filament\Resources\ProxyDoctorResource\Pages;
use App\Filament\Resources\ProxyDoctorResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewProxyDoctor extends ViewRecord
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
