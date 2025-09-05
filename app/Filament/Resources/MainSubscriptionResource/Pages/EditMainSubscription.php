<?php

namespace App\Filament\Resources\MainSubscriptionResource\Pages;

use App\Filament\Resources\MainSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainSubscription extends EditRecord
{
    protected static string $resource = MainSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
