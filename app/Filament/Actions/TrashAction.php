<?php

namespace App\Filament\Actions;

use Filament\Actions\DeleteAction as FilamentDeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TrashAction
{
    /**
     * Retourne une action "Mettre à la corbeille" qui met le status à 0 au lieu de supprimer.
     * Utilisable en remplacement de DeleteAction dans les tables.
     */
    public static function make(?string $label = null): DeleteAction
    {
        return DeleteAction::make()
            ->label($label ?? 'Mettre à la corbeille')
            ->modalHeading('Mettre à la corbeille')
            ->modalDescription('Cet élément sera déplacé dans la corbeille. Vous pourrez le restaurer en modifiant son statut.')
            ->successNotificationTitle('Mis à la corbeille')
            ->action(function (Model $record): void {
                static::trashRecord($record);
            });
    }

    /**
     * Retourne une action "Mettre à la corbeille" pour les pages Edit (header actions).
     */
    public static function makeForPage(?string $label = null): FilamentDeleteAction
    {
        return FilamentDeleteAction::make()
            ->label($label ?? 'Mettre à la corbeille')
            ->modalHeading('Mettre à la corbeille')
            ->modalDescription('Cet élément sera déplacé dans la corbeille. Vous pourrez le restaurer en modifiant son statut.')
            ->successNotificationTitle('Mis à la corbeille')
            ->action(function (): void {
                $record = $this->getRecord();
                static::trashRecord($record);
                $this->success();
                $this->redirect($this->getResource()::getUrl('index'));
            });
    }

    /**
     * Met un enregistrement à la corbeille (status = 0) ou utilise soft delete si pas de colonne status.
     */
    public static function trashRecord(Model $record): void
    {
        $table = $record->getTable();

        if (Schema::hasColumn($table, 'status')) {
            $record->update(['status' => 0]);
            Notification::make()
                ->title('Mis à la corbeille')
                ->body('L\'enregistrement a été déplacé dans la corbeille.')
                ->success()
                ->send();
        } elseif (method_exists($record, 'delete') && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($record))) {
            $record->delete();
            Notification::make()
                ->title('Mis à la corbeille')
                ->body('L\'enregistrement a été déplacé dans la corbeille.')
                ->success()
                ->send();
        } else {
            $record->delete();
        }
    }
}
