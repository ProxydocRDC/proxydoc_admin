<?php

namespace App\Filament\Imports;

use Filament\Forms;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\ChemProduct;
use App\Models\ChemCategory;              // possède un champ `code`
use App\Models\ChemManufacturer;          // pas de `code`, on matche sur `name`
use App\Models\ChemPharmaceuticalForm;    // on matche sur `name`

use App\Models\ChemPharmacy;
use App\Models\ChemPharmacyProduct;

class ChemProductImporter extends Importer
{
    protected static ?string $model = ChemProduct::class;

    public static function getLabel(): ?string       { return 'Import produits'; }
    public static function getPluralLabel(): ?string { return 'Import produits'; }

    /**
     * Options visibles dans le modal.
     */
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Toggle::make('create_refs')
                ->label('Créer catégories / fabricants / formes manquants')
                ->default(false),

            // Forms\Components\Toggle::make('dry_run')
            //     ->label('Simulation (ne rien enregistrer)')
            //     ->default(false),

            // // utile si ta Resource filtre par fournisseur, pour que ça marche même en queue
            // Forms\Components\Hidden::make('supplier_id')
            //     ->default(fn () => Auth::user()?->supplier?->id),
        ];
    }

    /**
     * Colonnes à mapper. On rend OBLIGATOIRES :
     * - name, generic_name, brand_name, price_ref
     * Le reste est facultatif.
     *
     * ⚠️ On N’ECRIT PAS directement les FKs ici. On laisse l’utilisateur saisir
     *   - `category_id` : le CODE de la catégorie (ex: ANALG)
     *   - `manufacturer_id` : le NOM du fabricant (ex: SANOFI)
     *   - `form_id` : le NOM de la forme galénique (ex: Comprimé)
     * Puis on résout tout dans beforeSave().
     */
    public static function getColumns(): array
    {
        return [
            // --- requis ---
            ImportColumn::make('name')
                ->label('Nom commercial')
                ->requiredMapping()
                ->rules(['required','string','max:500']),

            ImportColumn::make('generic_name')
                ->label('Nom générique')
                ->requiredMapping()
                ->rules(['required','string','max:500']),

            ImportColumn::make('brand_name')
                ->label('Marque')
                ->requiredMapping()
                ->rules(['required','string','max:100']),

            ImportColumn::make('price_ref')
                ->label('Prix de référence (USD)')
                ->requiredMapping()
                ->castStateUsing(fn($state) => static::toFloat($state))
                ->rules(['required','numeric','min:0']),

            // ————— Références OPTIONNELLES —————
            ImportColumn::make('category_id')            // l'utilisateur tape un CODE catégorie
                ->label('Code catégorie (facultatif)')
                ->castStateUsing(fn($state) => static::toTrimmedString($state)),

            ImportColumn::make('manufacturer_id')        // l'utilisateur tape un NOM fabricant
                ->label('Nom du fabricant (facultatif)')
                ->castStateUsing(fn($state) => static::toTrimmedString($state)),

            ImportColumn::make('form_id')                // l'utilisateur tape un NOM de forme
                ->label('Forme galénique (facultatif)')
                ->castStateUsing(fn($state) => static::toTrimmedString($state)),

            // ————— Autres champs optionnels —————
            ImportColumn::make('strength')->label('Dosage')
                ->castStateUsing(fn ($state) => static::toFloat($state)),
            ImportColumn::make('unit')->label('Unité'),
            ImportColumn::make('packaging')->label('Conditionnement'),
            ImportColumn::make('atc_code')->label('Code ATC'),

            ImportColumn::make('with_prescription')->label('Avec ordonnance ?')
                ->castStateUsing(fn ($state) => static::toBool($state)),

            ImportColumn::make('shelf_life_months')->label('Durée de conservation (mois)')
                ->castStateUsing(fn ($state) => static::toInt($state)),

            ImportColumn::make('indications')->label('Indications'),
            ImportColumn::make('contraindications')->label('Contre-indications'),
            ImportColumn::make('side_effects')->label('Effets secondaires'),
            ImportColumn::make('storage_conditions')->label('Conditions de stockage'),
            ImportColumn::make('description')->label('Description'),
            ImportColumn::make('composition')->label('Composition'),

            // Images : JSON ["a.jpg","b.jpg"] OU liste "a.jpg; b.jpg"
            ImportColumn::make('images')
                ->label('Images (liste ou JSON)')
                ->castStateUsing(function ($state) {
                    if ($state === null || $state === '') return null;
                    $s = trim((string) $state);
                    if (Str::startsWith($s, '[')) {
                        $arr = json_decode($s, true);
                        return is_array($arr) ? array_values($arr) : null;
                    }
                    $parts = preg_split('/[;,]+/', $s);
                    $parts = array_values(array_filter(array_map('trim', $parts)));
                    return $parts ?: null;
                }),
        ];
    }

    /**
     * Upsert : tente de retrouver un produit existant
     * (on matche par name + (category_id résolu) + (manufacturer_id résolu))
     * SANS créer de référence manquante ici.
     */
    public function resolveRecord(): ?ChemProduct
    {
        $name = static::toTrimmedString($this->data['name'] ?? '');
        if ($name === '') {
            return null;
        }

        $categoryId     = $this->lookupCategoryId($this->data['category_id'] ?? null, createIfMissing: false);
        $manufacturerId = $this->lookupManufacturerId($this->data['manufacturer_id'] ?? null, createIfMissing: false);

        $q = ChemProduct::query()->where('name', $name);
        if ($categoryId)     $q->where('category_id', $categoryId);
        if ($manufacturerId) $q->where('manufacturer_id', $manufacturerId);

        return $q->first();
    }

    /**
     * Remplit les champs "simples" (tout sauf les FK).
     * (les FK sont résolues dans beforeSave()).
     */
    public function fillRecord(): void
    {
        $this->record->name               = $this->data['name']               ?? $this->record->name;
        $this->record->generic_name       = $this->data['generic_name']       ?? $this->record->generic_name;
        $this->record->brand_name         = $this->data['brand_name']         ?? $this->record->brand_name;

        $this->record->price_ref          = $this->data['price_ref']          ?? $this->record->price_ref;
        $this->record->strength           = $this->data['strength']           ?? $this->record->strength;
        $this->record->unit               = $this->data['unit']               ?? $this->record->unit;
        $this->record->packaging          = $this->data['packaging']          ?? $this->record->packaging;
        $this->record->atc_code           = $this->data['atc_code']           ?? $this->record->atc_code;
        $this->record->with_prescription  = $this->data['with_prescription']  ?? $this->record->with_prescription;
        $this->record->shelf_life_months  = $this->data['shelf_life_months']  ?? $this->record->shelf_life_months;

        $this->record->indications        = $this->data['indications']        ?? $this->record->indications;
        $this->record->contraindications  = $this->data['contraindications']  ?? $this->record->contraindications;
        $this->record->side_effects       = $this->data['side_effects']       ?? $this->record->side_effects;
        $this->record->storage_conditions = $this->data['storage_conditions'] ?? $this->record->storage_conditions;

        $this->record->description        = $this->data['description']        ?? $this->record->description;
        $this->record->composition        = $this->data['composition']        ?? $this->record->composition;

        if (array_key_exists('images', $this->data)) {
            // nécessite $casts = ['images' => 'array'] dans le modèle
            $this->record->images = $this->data['images'];
        }
    }

    /**
     * Avant sauvegarde :
     * - Dry-run => on stoppe (aucune écriture)
     * - Résolution / création éventuelle des références à partir des colonnes *texte*
     * - Remplit supplier_id / created_by / updated_by
     */
    protected function beforeSave(): void
    {
        // if (($this->options['dry_run'] ?? false) === true) {
        //     $this->halt(); // pas d’écriture
        //     return;
        // }

        // Résolution des FK depuis les colonnes texte:
        $this->record->category_id     = $this->lookupCategoryId($this->data['category_id'] ?? null, createIfMissing: (bool) ($this->options['create_refs'] ?? false));
        $this->record->manufacturer_id = $this->lookupManufacturerId($this->data['manufacturer_id'] ?? null, createIfMissing: (bool) ($this->options['create_refs'] ?? false));
        $this->record->form_id         = $this->lookupFormId($this->data['form_id'] ?? null, createIfMissing: (bool) ($this->options['create_refs'] ?? false));

        // Méta
        // $this->record->supplier_id ??= (int) ($this->options['supplier_id'] ?? 0);
        $actorId = (int) (Auth::id() ?? config('app.system_user_id', 1));
        $this->record->created_by  ??= $actorId;
        $this->record->updated_by    = $actorId;
    }

    // ---------------------------------------------------------------------
    // Helpers de résolution des références + helpers de normalisation simple
    // ---------------------------------------------------------------------

    /** `category_id` (texte) -> id via ChemCategory::code */
    private function lookupCategoryId(?string $raw, bool $createIfMissing): ?int
    {
        $code = static::toTrimmedString($raw);
        if ($code === '') return null;

        $id = ChemCategory::where('code', $code)->value('id');
        if (!$id && $createIfMissing) {
            $id = ChemCategory::create([
                'status'     => 1,
                'code'       => $code,
                'name'       => $code,
                'created_by' => Auth::id() ?? config('app.system_user_id', 1),
                'updated_by' => Auth::id() ?? config('app.system_user_id', 1),
            ])->id;
        }
        return $id ?: null;
    }

    /** `manufacturer_id` (texte) -> id via ChemManufacturer::name */
    private function lookupManufacturerId(?string $raw, bool $createIfMissing): ?int
    {
        $name = static::toTrimmedString($raw);
        if ($name === '') return null;

        $id = ChemManufacturer::where('name', $name)->value('id');
        if (!$id && $createIfMissing) {
            $id = ChemManufacturer::create([
                'status'     => 1,
                'name'       => $name,
                'created_by' => Auth::id() ?? config('app.system_user_id', 1),
                'updated_by' => Auth::id() ?? config('app.system_user_id', 1),
            ])->id;
        }
        return $id ?: null;
    }

    /** `form_id` (texte) -> id via ChemPharmaceuticalForm::name */
    private function lookupFormId(?string $raw, bool $createIfMissing): ?int
    {
        $name = static::toTrimmedString($raw);
        if ($name === '') return null;

        $id = class_exists(ChemPharmaceuticalForm::class)
            ? ChemPharmaceuticalForm::where('name', $name)->value('id')
            : null;

        if (!$id && $createIfMissing && class_exists(ChemPharmaceuticalForm::class)) {
            $id = ChemPharmaceuticalForm::create([
                'status'     => 1,
                'name'       => $name,
                'created_by' => Auth::id() ?? config('app.system_user_id', 1),
                'updated_by' => Auth::id() ?? config('app.system_user_id', 1),
            ])->id;
        }
        return $id ?: null;
    }

    // --- petits helpers de cast ---
    protected static function toTrimmedString($v): string
    {
        return trim((string) $v);
    }

    protected static function toFloat($v): ?float
    {
        if ($v === null || $v === '') return null;
        $s = str_replace(' ', '', (string) $v);
        if (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        }
        return is_numeric($s) ? (float) $s : null;
    }

    protected static function toBool($v): bool
    {
        $s = strtolower(trim((string) $v));
        return in_array($s, ['1','true','yes','y','oui','o'], true);
    }

    protected static function toInt($v): ?int
    {
        if ($v === null || $v === '') return null;
        return ctype_digit((string) $v) ? (int) $v : null;
    }


    /** Titre du toast à la fin */
    public static function getCompletedNotificationTitle(Import $import): string
    {
        return 'Import des produits terminé';
    }

    /** Corps du toast (avec compteurs) */
    public static function getCompletedNotificationBody(Import $import): string
    {
        // suivant les versions de Filament, les accessor varient, on reste défensif
        $ok = $import->successful_rows ?? ($import->successfulRows ?? null);
        $ko = method_exists($import, 'getFailedRowsCount')
            ? $import->getFailedRowsCount()
            : ($import->failed_rows ?? ($import->failedRows ?? null));

        if (is_numeric($ok) && is_numeric($ko)) {
            return "Lignes OK : {$ok} • Erreurs : {$ko}";
        }

        return 'Import terminé.';
    }

    /** Icône (Heroicons) */
    public static function getCompletedNotificationIcon(): ?string
    {
        return 'heroicon-o-check-circle';
    }

    /** Couleur du toast */
    public static function getCompletedNotificationColor(): string
    {
        return 'success'; // success | warning | danger | info | gray...
    }

    /** Durée d’affichage (ms) – optionnel */
    public static function getCompletedNotificationDuration(): ?int
    {
        return 8000; // 8 secondes
    }

    /**
 * Après la sauvegarde d’une ligne (création ou update),
 * on rattache le produit à la pharmacie par défaut "Pharmacie Proxydoc".
 */
protected function afterSave(): void
{
    $product = $this->record;
    if (! $product?->id) {
        return;
    }

    // 1) Récupérer (ou créer) la pharmacie par défaut
    $pharmacyId = ChemPharmacy::query()
        ->where('name', 'Pharmacie Proxydoc') // nom lisible
        ->orWhere('code', 'PROXYDOC')         // code technique si présent
        ->value('id');

    if (! $pharmacyId) {
        $pharmacy = ChemPharmacy::create([
            'status'      => 1,
            'name'        => 'Pharmacie Proxydoc',
            'code'        => 'PROXYDOC',              // si ta table a la colonne code
            'created_by'  => Auth::id() ?? config('app.system_user_id', 1),
            'updated_by'  => Auth::id() ?? config('app.system_user_id', 1),
            'user_id'     => Auth::id() ?? config('app.system_user_id', 1),         // adapte si nécessaire
            'supplier_id' => 0,                       // adapte si nécessaire
            'zone_id'     => 1,                       // adapte si nécessaire
        ]);
        $pharmacyId = $pharmacy->id;
    }

    // 2) Lier le produit à cette pharmacie (évite les doublons)
    ChemPharmacyProduct::firstOrCreate(
        [
            'pharmacy_id' => $pharmacyId,
            'product_id'  => $product->id,
        ],
        [
            'status'     => 1,
            // On peut reprendre un prix par défaut depuis le produit (ex: price_ref) :
            'sale_price' => (float) ($product->price_ref ?? 0),
            'currency'   => 'USD',    // ou 'CDF' selon ton contexte / valeur par défaut
            'stock_qty'  => 50,
            'reorder_level'  => 5,
            'created_by' => Auth::id() ?? config('app.system_user_id', 1),
            'updated_by' => Auth::id() ?? config('app.system_user_id', 1),
        ]
    );
}

}
