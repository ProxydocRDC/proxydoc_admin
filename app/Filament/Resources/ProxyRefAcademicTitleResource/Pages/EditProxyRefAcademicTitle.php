<?php

namespace App\Filament\Resources\ProxyRefAcademicTitleResource\Pages;

use App\Filament\Resources\ProxyRefAcademicTitleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProxyRefAcademicTitle extends EditRecord
{
    protected static string $resource = ProxyRefAcademicTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
