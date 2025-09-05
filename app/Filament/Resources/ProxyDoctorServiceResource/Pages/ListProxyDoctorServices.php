<?php

namespace App\Filament\Resources\ProxyDoctorServiceResource\Pages;

use App\Filament\Resources\ProxyDoctorServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyDoctorServices extends ListRecords
{
    protected static string $resource = ProxyDoctorServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
