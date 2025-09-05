<?php

namespace App\Support\Filament;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait RestrictToOwner
{
    /** Colonne qui désigne le propriétaire du record (par défaut: created_by) */
    protected static function ownerColumn(): string
    {
        return 'created_by';
    }

    /** Valeur (id) du propriétaire courant */
    protected static function ownerId(): ?int
    {
        return Auth::id();
    }

    /** Permission Shield qui permet d’ignorer le scope (admin/manager) */
    protected static function bypassPermission(): ?string
    {
        // ex: 'view_all_' . static::getSlug();
        return 'view_all_' . str_replace('/', '_', static::getSlug());
    }

    /** Scoper l’index à l’utilisateur connecté (sauf bypass) */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();
        if (! $user) {
            return $query->whereRaw('1=0');
        }

        $bypass = static::bypassPermission();
        if ($bypass && $user->can($bypass)) {
            return $query;
        }

        return $query->where(static::ownerColumn(), static::ownerId());
    }

    /** Verrous d’édition/suppression (si tu n’utilises pas de Policy) */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        $bypass = static::bypassPermission();
        if ($bypass && $user->can($bypass)) return true;

        return (int) $record->{static::ownerColumn()} === (int) static::ownerId();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }


    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        $bypass = static::bypassPermission();
        if ($bypass && $user->can($bypass)) return true;

        return (int) $record->{static::ownerColumn()} === (int) static::ownerId();
    }
}
