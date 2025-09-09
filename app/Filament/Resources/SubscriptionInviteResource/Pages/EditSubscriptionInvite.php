<?php

namespace App\Filament\Resources\SubscriptionInviteResource\Pages;

use App\Filament\Resources\SubscriptionInviteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionInvite extends EditRecord
{
    protected static string $resource = SubscriptionInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
