<?php

namespace App\Support;

use App\Models\User;
use App\Models\ChemShipment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class CourierHelper
{
    /**
     * Livreurs "libres" pour un jour donné.
     * Libre = n'a PAS de shipment aujourd'hui avec un statut "occupant" (shipped|transit).
     */
    public static function freeCouriersQuery(
        ?string $search = null,
        ?CarbonInterface $day = null,
        array $busyStatuses = ['shipped', 'transit'] // statuts qui rendent indisponible
    ): Builder {
        $day ??= now();
        $start = $day->copy()->startOfDay();
        $end   = $day->copy()->endOfDay();

        return User::query()
            // Rôle "livreur" (Spatie) — adapte si tu n'utilises pas Spatie
            // ->whereHas('roles', fn ($r) => $r->where('name', 'livreur'))
       ->where('default_role',"=", 3)

            // Ne doit PAS avoir aujourd'hui un shipment "occupant"
            ->whereDoesntHave('shipments', function (Builder $s) use ($start, $end, $busyStatuses) {
                $s->whereNull('deleted_at')
                  ->whereBetween('shipped_at', [$start, $end])
                  ->whereIn('shipment_status', $busyStatuses);
            })

            // Recherche plein-texte
            ->when($search, fn ($q) =>
                $q->where(fn ($q) => $q
                    ->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname',  'like', "%{$search}%")
                    ->orWhere('phone',     'like', "%{$search}%")
                    ->orWhere('email',     'like', "%{$search}%")
                )
            );
    }

    /** options id => fullname (pour Select Filament) */
    public static function freeCourierOptions(?string $search = null, ?CarbonInterface $day = null): array
    {
        return self::freeCouriersQuery($search, $day)
            ->orderBy('lastname')->orderBy('firstname')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->fullname])
            ->toArray();
    }
}
