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
            \App\Filament\Actions\TrashAction::makeForPage(),
            Actions\EditAction::make(),
        ];
    }
}
