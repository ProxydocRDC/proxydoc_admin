<?php

namespace App\Filament\Resources\ProxyDoctorResource\Pages;

use App\Filament\Resources\ProxyDoctorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyDoctor extends EditRecord
{
    protected static string $resource = ProxyDoctorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
