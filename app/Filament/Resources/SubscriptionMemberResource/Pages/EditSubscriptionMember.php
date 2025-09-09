<?php

namespace App\Filament\Resources\SubscriptionMemberResource\Pages;

use App\Filament\Resources\SubscriptionMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionMember extends EditRecord
{
    protected static string $resource = SubscriptionMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
