<?php

namespace App\Filament\Resources\ProxyDoctorServiceResource\Pages;

use App\Filament\Resources\ProxyDoctorServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyDoctorService extends EditRecord
{
    protected static string $resource = ProxyDoctorServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
