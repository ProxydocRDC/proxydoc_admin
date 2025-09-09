<?php

namespace App\Filament\Resources\ProxyPlanFeatureResource\Pages;

use App\Filament\Resources\ProxyPlanFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProxyPlanFeatures extends ListRecords
{
    protected static string $resource = ProxyPlanFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
