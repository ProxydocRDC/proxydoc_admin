<?php

namespace App\Filament\Resources\ProxyServiceResource\Pages;

use App\Filament\Resources\ProxyServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyServices extends ListRecords
{
    protected static string $resource = ProxyServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
