<?php

namespace App\Filament\Resources\ProxyPatientResource\Pages;

use App\Filament\Resources\ProxyPatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyPatients extends ListRecords
{
    protected static string $resource = ProxyPatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
