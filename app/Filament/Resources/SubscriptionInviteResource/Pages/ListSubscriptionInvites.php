<?php

namespace App\Filament\Resources\SubscriptionInviteResource\Pages;

use App\Filament\Resources\SubscriptionInviteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionInvites extends ListRecords
{
    protected static string $resource = SubscriptionInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
