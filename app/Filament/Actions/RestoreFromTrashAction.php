<?php

namespace App\Filament\Actions;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RestoreFromTrashAction
{
    /**
     * Retourne une action "Restaurer" qui remet le status à 1 pour les enregistrements à la corbeille.
     */
    public static function make(?string $label = null): Action
    {
        return Action::make('restoreFromTrash')
            ->label($label ?? 'Restaurer')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Restaurer')
            ->modalDescription('Cet élément sera restauré (statut actif).')
            ->visible(fn (Model $record): bool => Schema::hasColumn($record->getTable(), 'status') && (int) ($record->status ?? 1) === 0)
            ->action(function (Model $record): void {
                $record->update(['status' => 1]);
                Notification::make()
                    ->title('Restauré')
                    ->body('L\'enregistrement a été restauré.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Version bulk.
     */
    public static function makeBulk(?string $label = null): \Filament\Tables\Actions\BulkAction
    {
        return \Filament\Tables\Actions\BulkAction::make('restoreFromTrashBulk')
            ->label($label ?? 'Restaurer')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Restaurer')
            ->modalDescription('Les éléments sélectionnés seront restaurés.')
            ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                $count = 0;
                foreach ($records as $record) {
                    if (Schema::hasColumn($record->getTable(), 'status') && (int) ($record->status ?? 1) === 0) {
                        $record->update(['status' => 1]);
                        $count++;
                    }
                }
                Notification::make()
                    ->title('Restaurés')
                    ->body($count . ' enregistrement(s) restauré(s).')
                    ->success()
                    ->send();
            });
    }
}
