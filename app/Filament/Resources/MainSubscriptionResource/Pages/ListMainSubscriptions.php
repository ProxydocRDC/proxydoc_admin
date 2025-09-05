<?php

namespace App\Filament\Resources\MainSubscriptionResource\Pages;

use App\Filament\Resources\MainSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMainSubscriptions extends ListRecords
{
    protected static string $resource = MainSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvel abonnement')
                ->icon('heroicon-o-credit-card'),
        ];
    }
}
