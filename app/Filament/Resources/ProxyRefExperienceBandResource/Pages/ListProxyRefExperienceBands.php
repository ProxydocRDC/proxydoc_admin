<?php

namespace App\Filament\Resources\ProxyRefExperienceBandResource\Pages;

use App\Filament\Resources\ProxyRefExperienceBandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyRefExperienceBands extends ListRecords
{
    protected static string $resource = ProxyRefExperienceBandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
