<?php

namespace App\Filament\Resources\UserPatientResource\Pages;

use App\Filament\Resources\ProxyPatientResource;
use App\Filament\Resources\UserPatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserPatients extends ListRecords
{
    protected static string $resource = UserPatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_patient')
                ->label('Nouveau patient')
                ->icon('heroicon-o-plus')
                ->url(ProxyPatientResource::getUrl('create')),
        ];
    }
}
