<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Trait pour les Resources qui utilisent status=0 comme "corbeille" au lieu de suppression réelle.
 * - Exclut par défaut les enregistrements avec status=0
 * - Fournit un filtre "Afficher la corbeille" pour les voir
 */
trait HasTrashableRecords
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return static::applyTrashFilter($query);
    }

    /**
     * Applique le filtre corbeille (exclut status=0) sur une requête.
     * Utile pour les Resources avec getEloquentQuery personnalisé.
     */
    public static function applyTrashFilter(Builder $query): Builder
    {
        $model = static::getModel();
        $table = (new $model)->getTable();

        if (Schema::hasColumn($table, 'status')) {
            return $query->where(function (Builder $q) use ($table) {
                $q->where($table . '.status', '>', 0)
                    ->orWhereNull($table . '.status');
            });
        }

        return $query;
    }

    /**
     * Retourne le filtre pour afficher la corbeille (status=0).
     * À ajouter dans ->filters([...]) de la table.
     */
    public static function getTrashFilter(): ?\Filament\Tables\Filters\TernaryFilter
    {
        $model = static::getModel();
        $table = (new $model)->getTable();

        if (! Schema::hasColumn($table, 'status')) {
            return null;
        }

        return \Filament\Tables\Filters\TernaryFilter::make('trashed')
            ->label('Corbeille')
            ->placeholder('Masquer les éléments supprimés')
            ->trueLabel('Afficher uniquement la corbeille')
            ->falseLabel('Masquer la corbeille')
            ->queries(
                true: fn (Builder $query) => $query->where($table . '.status', 0),
                false: fn (Builder $query) => $query->where(function (Builder $q) use ($table) {
                    $q->where($table . '.status', '>', 0)->orWhereNull($table . '.status');
                }),
                blank: fn (Builder $query) => $query->where(function (Builder $q) use ($table) {
                    $q->where($table . '.status', '>', 0)->orWhereNull($table . '.status');
                }),
            );
    }
}
