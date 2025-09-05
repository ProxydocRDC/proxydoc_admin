<?php

namespace App\Support\Filament;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait RestrictToSupplierOld
{
    /** Permission Shield pour bypass (admin) */
    protected static function bypassPermission(): string
    {
        return 'view_all_' . str_replace('/', '_', static::getSlug());
    }

    /** ID du fournisseur lié à l'utilisateur connecté */
    protected static function supplierId(): ?int
    {
        return Auth::user()?->supplier?->id;
    }

    /** Query de base des index */
    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) return $q->whereRaw('1=0');                   // non connecté => rien
        if ($user->can(static::bypassPermission())) return $q;     // admin => tout

        $supplierId = static::supplierId();
        if (! $supplierId) return $q->whereRaw('1=0');             // pas fournisseur => rien

        return static::scopeToSupplier($q, $supplierId);
    }
 /** Helpers */
    protected static function applySupplierPathConstraint(Builder $q, string $path, int $supplierId): Builder
    {
        if (! str_contains($path, '.')) {
            return $q->where($path, $supplierId);
        }

        [$relation, $rest] = explode('.', $path, 2);

        return $q->whereHas($relation, function ($qq) use ($rest, $supplierId) {
            if (str_contains($rest, '.')) {
                return static::applySupplierPathConstraint($qq, $rest, $supplierId);
            }
            return $qq->where($rest, $supplierId);
        });
    }

    /** Par défaut: table avec colonne supplier_id (override si besoin) */
    protected static function scopeToSupplier(Builder $q, int $supplierId): Builder
    {
        return $q->where('supplier_id', $supplierId);
    }

    /** Sécurité accès en lecture/édition/suppression (si pas de Policy) */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        if ($user->can(static::bypassPermission())) return true;

        return (int) ( $record->supplier_id ?? 0 ) === (int) static::supplierId();
    }
    public static function canEdit(Model $record): bool  { return static::canView($record); }
    public static function canDelete(Model $record): bool{ return static::canView($record); }

    /** Remplissage automatique à la création */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['supplier_id'] = $data['supplier_id'] ?? static::supplierId();
        $data['created_by']  = $data['created_by']  ?? Auth::id();
        $data['updated_by']  = $data['updated_by']  ?? Auth::id();
        return $data;
    }
}
