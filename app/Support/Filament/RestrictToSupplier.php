<?php
namespace App\Support\Filament;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait RestrictToSupplier
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

    /**
     * Chemin vers l’owner fournisseur pour cette resource.
     * Par défaut: colonne directe 'supplier_id'.
     * Override dans les resources qui passent par une relation (ex: 'pharmacy.supplier_id').
     */
    protected static function supplierOwnerPath(): string
    {
        return 'supplier_id';
    }

    /** Query de base des index */
    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $q->whereRaw('1=0'); // non connecté => rien
        }

        if ($user->can(static::bypassPermission())) {
            return $q; // admin => tout
        }

        $supplierId = static::supplierId();
        if (! $supplierId) {
            return $q->whereRaw('1=0'); // pas fournisseur => rien
        }

        // <-- IMPORTANT : on applique le filtre via le chemin (champ direct OU relation)
        return static::applySupplierPathConstraint($q, static::supplierOwnerPath(), $supplierId);
    }

    /** Applique un where via un chemin (ex: 'supplier_id' ou 'pharmacy.supplier_id') */
    protected static function applySupplierPathConstraint(Builder $q, string $path, int $supplierId): Builder
    {
        if (! str_contains($path, '.')) {
            return $q->where($path, $supplierId);
        }

        [$relation, $rest] = explode('.', $path, 2);

        return $q->whereHas($relation, function (Builder $qq) use ($rest, $supplierId) {
            if (str_contains($rest, '.')) {
                return static::applySupplierPathConstraint($qq, $rest, $supplierId);
            }
            return $qq->where($rest, $supplierId);
        });
    }

    /** Compatibilité: si une resource override cette méthode, on la laisse faire */
    // protected static function scopeToSupplier(Builder $q, int $supplierId): Builder
    // {
    //     // Délègue au chemin par défaut
    //     return static::applySupplierPathConstraint($q, static::supplierOwnerPath(), $supplierId);
    // }


protected static function scopeToSupplier(Builder $q, int $supplierId): Builder
{
    // filtre via la relation pharmacy -> supplier_id
    return $q->whereHas('pharmacy', fn ($p) => $p->where('supplier_id', $supplierId));
}

    /** -------- Sécurité accès en lecture/édition/suppression -------- */

    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        if ($user->can(static::bypassPermission())) return true;

        return static::recordBelongsToSupplier($record);
    }
    public static function canEdit(Model $record): bool   { return static::canView($record); }
    public static function canDelete(Model $record): bool { return static::canView($record); }

    protected static function recordBelongsToSupplier(Model $record): bool
    {
        $supplierId = static::supplierId();
        if (! $supplierId) return false;

        $value = static::readPath($record, static::supplierOwnerPath());
        return (int) $value === (int) $supplierId;
    }

    /** Lit une valeur sur le record via un chemin (ex: 'pharmacy.supplier_id') */
    protected static function readPath(Model $record, string $path)
    {
        $current = $record;
        foreach (explode('.', $path) as $segment) {
            if (! isset($current)) return null;
            $current = $current->{$segment};
        }
        return $current;
    }

    /** Remplissage automatique à la création (si la colonne existe dans le form) */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('supplier_id', $data) && empty($data['supplier_id'])) {
            $data['supplier_id'] = static::supplierId();
        }
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['updated_by'] = $data['updated_by'] ?? Auth::id();
        return $data;
    }
}
