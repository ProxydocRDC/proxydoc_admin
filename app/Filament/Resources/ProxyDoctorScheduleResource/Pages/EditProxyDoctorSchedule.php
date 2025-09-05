<?php

namespace App\Filament\Resources\ProxyDoctorScheduleResource\Pages;

use App\Filament\Resources\ProxyDoctorScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyDoctorSchedule extends EditRecord
{
    protected static string $resource = ProxyDoctorScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
