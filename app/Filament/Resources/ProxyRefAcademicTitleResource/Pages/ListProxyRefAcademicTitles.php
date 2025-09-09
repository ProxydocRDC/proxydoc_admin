<?php

namespace App\Filament\Resources\ProxyRefAcademicTitleResource\Pages;

use App\Filament\Resources\ProxyRefAcademicTitleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyRefAcademicTitles extends ListRecords
{
    protected static string $resource = ProxyRefAcademicTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
