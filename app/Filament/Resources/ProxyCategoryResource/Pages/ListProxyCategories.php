<?php

namespace App\Filament\Resources\ProxyCategoryResource\Pages;

use App\Filament\Resources\ProxyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyCategories extends ListRecords
{
    protected static string $resource = ProxyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
