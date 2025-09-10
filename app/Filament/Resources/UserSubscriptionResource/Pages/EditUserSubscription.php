<?php

namespace App\Filament\Resources\UserSubscriptionResource\Pages;

use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\UserSubscriptionResource;

class EditUserSubscription extends EditRecord
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record; // App\Models\UserSubscription
        $now = Carbon::today();

        $isCurrentlyActive = ($record->subscription_status === 'active')
            && $record->start_date
            && $record->end_date
            && $record->start_date->lte($now)
            && $record->end_date->gte($now);

        $wantsToChangeStatus =
            array_key_exists('subscription_status', $data)
            && $data['subscription_status'] !== $record->subscription_status;

        if ($isCurrentlyActive && $wantsToChangeStatus) {
            throw ValidationException::withMessages([
                'subscription_status' => "Impossible de modifier le statut : l’abonnement est actuellement en cours (du {$record->start_date->format('d/m/Y')} au {$record->end_date->format('d/m/Y')}).",
            ]);
        }

        return $data;
    }
}
