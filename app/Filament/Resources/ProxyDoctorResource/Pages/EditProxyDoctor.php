<?php

namespace App\Filament\Resources\ProxyDoctorResource\Pages;

use App\Filament\Resources\ProxyDoctorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
class EditProxyDoctor extends EditRecord
{
    protected static string $resource = ProxyDoctorResource::class;


    /* ---------- Helpers: trouver l'ID précédent / suivant selon la même query que la Resource ---------- */

    protected function baseQuery(): Builder
    {
        // Utilise exactement la requête de la Resource (scopes/tenancy/soft deletes inclus)
        return static::getResource()::getEloquentQuery();
    }

    protected function findAdjacentId(string $dir = 'next'): ?int
    {
        $pk = $this->record->getKeyName();      // généralement 'id'
        $id = $this->record->getKey();

        $q = (clone $this->baseQuery());

        if ($dir === 'prev') {
            return $q->where($pk, '<', $id)->orderByDesc($pk)->value($pk);
        }

        return $q->where($pk, '>', $id)->orderBy($pk)->value($pk);
    }

    protected function editUrlForId(int $id): string
    {
        return static::getResource()::getUrl('edit', ['record' => $id]);
    }

    /* ---------- Boutons dans l’entête (Précédent / Suivant) ---------- */

    protected function getHeaderActions(): array
    {
        $prevId = $this->findAdjacentId('prev');
        $nextId = $this->findAdjacentId('next');

        return [
                 \App\Filament\Actions\TrashAction::makeForPage(),
            // Ex: Delete, View, etc. si tu en as
            Actions\Action::make('prev')
                ->label('Précédent')->keyBindings(['alt+arrow-left'])   // pour "prev"
                ->icon('heroicon-m-chevron-left')
                ->disabled(fn () => ! $this->findAdjacentId('prev'))
                ->url(fn () => ($id = $this->findAdjacentId('prev')) ? $this->editUrlForId($id) : null)
                ->extraAttributes(['wire:navigate' => true]),

            Actions\Action::make('next')
                ->label('Suivant')->keyBindings(['alt+arrow-right'])  // pour "next"
                ->icon('heroicon-m-chevron-right')
                ->color('primary')
                ->disabled(fn () => ! $this->findAdjacentId('next'))
                ->url(fn () => ($id = $this->findAdjacentId('next')) ? $this->editUrlForId($id) : null)
                ->extraAttributes(['wire:navigate' => true]),
        ];
    }

    /* ---------- Actions du formulaire (Enregistrer & Suivant / Enregistrer & Précédent) ---------- */

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Actions\Action::make('savePrev')
                ->label('Enregistrer & précédent')
                ->icon('heroicon-m-arrow-left-circle')
                ->action(function () {
                    $this->save();                                   // enregistre l’enregistrement
                    if ($id = $this->findAdjacentId('prev')) {
                        $this->redirect($this->editUrlForId($id));   // navigation propre depuis une Page
                    }
                })
                ->disabled(fn () => ! $this->findAdjacentId('prev')),

            Actions\Action::make('saveNext')
                ->label('Enregistrer & suivant')
                ->icon('heroicon-m-arrow-right-circle')
                ->color('primary')
                ->action(function () {
                    $this->save();
                    if ($id = $this->findAdjacentId('next')) {
                        $this->redirect($this->editUrlForId($id));
                    }
                })
                ->disabled(fn () => ! $this->findAdjacentId('next')),

            $this->getCancelFormAction(),
        ];
    }
}
