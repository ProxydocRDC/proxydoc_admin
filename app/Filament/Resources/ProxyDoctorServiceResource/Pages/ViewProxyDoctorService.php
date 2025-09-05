<?php
namespace App\Filament\Resources\ProxyDoctorServiceResource\Pages;

use App\Filament\Resources\ProxyDoctorServiceResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
class ViewProxyDoctorService extends ViewRecord {
    protected static string $resource = ProxyDoctorServiceResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\EditAction::make(),
        ];
    }
}
