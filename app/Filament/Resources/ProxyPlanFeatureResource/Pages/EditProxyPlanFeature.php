<?php

namespace App\Filament\Resources\ProxyPlanFeatureResource\Pages;

use App\Filament\Resources\ProxyPlanFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyPlanFeature extends EditRecord
{
    protected static string $resource = ProxyPlanFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
