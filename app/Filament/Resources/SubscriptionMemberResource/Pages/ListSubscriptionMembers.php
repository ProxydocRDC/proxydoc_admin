<?php

namespace App\Filament\Resources\SubscriptionMemberResource\Pages;

use App\Filament\Resources\SubscriptionMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionMembers extends ListRecords
{
    protected static string $resource = SubscriptionMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
