<?php

namespace App\Filament\Resources\ProxyRefHospitalTierResource\Pages;

use App\Filament\Resources\ProxyRefHospitalTierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyRefHospitalTiers extends ListRecords
{
    protected static string $resource = ProxyRefHospitalTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
