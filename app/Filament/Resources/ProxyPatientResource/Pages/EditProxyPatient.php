<?php

namespace App\Filament\Resources\ProxyPatientResource\Pages;

use App\Filament\Resources\ProxyPatientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyPatient extends EditRecord
{
    protected static string $resource = ProxyPatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
