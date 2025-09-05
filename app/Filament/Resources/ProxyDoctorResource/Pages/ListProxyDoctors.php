<?php

namespace App\Filament\Resources\ProxyDoctorResource\Pages;

use App\Filament\Resources\ProxyDoctorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyDoctors extends ListRecords
{
    protected static string $resource = ProxyDoctorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
