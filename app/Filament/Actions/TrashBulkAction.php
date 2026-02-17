<?php

namespace App\Filament\Actions;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TrashBulkAction
{
    /**
     * Retourne une action bulk "Mettre à la corbeille" qui met le status à 0 au lieu de supprimer.
     */
    public static function make(?string $label = null): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->label($label ?? 'Mettre à la corbeille')
            ->modalHeading('Mettre à la corbeille')
            ->modalDescription('Les éléments sélectionnés seront déplacés dans la corbeille.')
            ->successNotificationTitle('Mis à la corbeille')
            ->action(function (Collection $records): void {
                static::trashRecords($records);
            });
    }

    /**
     * Met plusieurs enregistrements à la corbeille.
     */
    public static function trashRecords(Collection $records): void
    {
        $count = 0;

        foreach ($records as $record) {
            $table = $record->getTable();

            if (Schema::hasColumn($table, 'status')) {
                $record->update(['status' => 0]);
                $count++;
            } elseif (method_exists($record, 'delete') && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($record))) {
                $record->delete();
                $count++;
            } else {
                $record->delete();
                $count++;
            }
        }

        Notification::make()
            ->title('Mis à la corbeille')
            ->body($count . ' enregistrement(s) déplacé(s) dans la corbeille.')
            ->success()
            ->send();
    }
}
