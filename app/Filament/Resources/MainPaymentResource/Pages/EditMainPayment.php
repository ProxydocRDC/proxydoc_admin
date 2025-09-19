<?php

namespace App\Filament\Resources\MainPaymentResource\Pages;

use App\Filament\Resources\MainPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainPayment extends EditRecord
{
    protected static string $resource = MainPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
