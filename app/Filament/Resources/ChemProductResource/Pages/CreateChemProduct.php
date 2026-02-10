<?php

namespace App\Filament\Resources\ChemProductResource\Pages;

use Filament\Actions;
use App\Models\ChemPharmacy;
use App\Models\ChemPharmacyProduct;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ChemProductResource;

class CreateChemProduct extends CreateRecord
{
    protected static string $resource = ChemProductResource::class;

    /**
     * Méthode hook appelée par Filament juste après la création
     * du produit (après l'INSERT sur la table des produits).
     */
    protected function afterCreate(): void
    {
        // $this->record = instance Eloquent du produit qui vient d'être créé
        $product = $this->record;

        /**
         * 1) Récupérer l'ID de la pharmacie "PROXYDOC".
         *    - on cherche d'abord par name = 'PROXYDOC'
         *    - puis, si ta table a une colonne 'code', on accepte aussi code = 'PROXYDOC'
         *    -> value('id') renvoie l'id de la 1re ligne trouvée ou null si rien.
         */
        $pharmacyId = ChemPharmacy::query()
            ->where('name', 'PROXYDOC')
            ->orWhere('code', 'PROXYDOC')
            ->value('id');

        /**
         * 2) Si elle n'existe pas, on la crée.
         *    - firstOrCreate() tente d'abord un SELECT avec les attributs de recherche,
         *      puis fait un INSERT si aucune ligne ne correspond.
         *    - On met des valeurs par défaut minimales pour satisfaire les NOT NULL.
         *    - Auth::id() ?? 1 : renseigne l'user courant, sinon l'id 1 par défaut.
         */
        if (! $pharmacyId) {
            $pharmacyId = ChemPharmacy::firstOrCreate(
                ['name' => 'PROXYDOC'],      // critères d'unicité/recherche
                [
                    'status'      => 1,
                    'created_by'  => Auth::id() ?? config('app.system_user_id', 1),
                    'user_id'     => Auth::id() ?? config('app.system_user_id', 1),
                    'supplier_id' => 0,
                    'zone_id'     => 1,
                    // ajoute ici d'autres colonnes requises par ton schéma si besoin
                ]
            )->id;
        }

        /**
         * 3) Créer (ou récupérer) le lien produit <-> pharmacie.
         *    Table de liaison: chem_pharmacy_products
         *    - firstOrCreate évite un doublon si l'association existe déjà.
         *    - On met des valeurs par défaut : status, prix, devise, stock, created_by.
         *    - Ajuste sale_price/currency/stock_qty si tu veux reprendre des valeurs du formulaire.
         */
        ChemPharmacyProduct::firstOrCreate(
            [   // critères d'unicité/recherche
                'pharmacy_id' => $pharmacyId,
                'product_id'  => $product->id,
            ],
            [   // valeurs par défaut si on insère
                'status'     => 1,
                'sale_price' => 0,         // défaut si pas saisi sur le form produit
                'currency'   => 'USD',
                'stock_qty'  => 0,
                'created_by' => Auth::id() ?? config('app.system_user_id', 1),
            ]
        );
    }
}
