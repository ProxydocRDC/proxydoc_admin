<?php

namespace App\Imports;

use Maatwebsite\Excel\Row;
use App\Models\ChemProduct;
use Illuminate\Support\Arr;
use App\Models\ChemCategory;
use Illuminate\Validation\Rule;
use App\Models\ChemManufacturer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\ChemPharmaceuticalForm;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ChemProductsImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use SkipsFailures;

    /** Compteurs pour le récap */
    public int $created = 0;
    public int $updated = 0;

    /** Politique d’upsert :
     * - si `sku` présent → on upsert par `sku`
     * - sinon on crée toujours un nouveau (tu peux changer selon ta logique)
     */
    protected bool $upsertBySku = true;

    /** Cache simple pour éviter des requêtes répétées sur les FKs */
    protected array $cache = [
        'categories' => [],
        'manufacturers' => [],
        'forms' => [],
    ];

    public function onRow(Row $row): void
    {
        $r = $row->toArray(); // clés = entêtes normalisées (snake case)

        // 1) VALIDER les 4 obligatoires (déjà couvert par rules() + WithValidation)
        // Ici, on récupère les valeurs (trim) :
        $name         = trim((string) Arr::get($r, 'name', ''));
        $genericName  = trim((string) Arr::get($r, 'generic_name', ''));
        $brandName    = trim((string) Arr::get($r, 'brand_name', ''));
        $priceRef     = (float)  Arr::get($r, 'price_ref', 0);

        // 2) Résoudre les FKs optionnelles
        $categoryId    = $this->resolveCategoryId(Arr::get($r, 'category_code'));
        $manufacturerId= $this->resolveManufacturerId(Arr::get($r, 'manufacturer_name'));
        $formId        = $this->resolveFormId(Arr::get($r, 'form_name'));

        // 3) Préparer le payload des champs
        $payload = [
            'name'          => $name,
            'generic_name'  => $genericName,
            'brand_name'    => $brandName,
            'price_ref'     => $priceRef,

            // FK optionnelles
            'category_id'     => $categoryId,
            'manufacturer_id' => $manufacturerId,
            'form_id'         => $formId,
            'images'         => "default.png",
            'created_by'         => Auth::id(),

            // Champs optionnels (ne plantent pas s’ils sont manquants)
            // 'sku'           => Arr::get($r, 'sku'),        // string|null
            // 'barcode'       => Arr::get($r, 'barcode'),
            // 'strength'      => Arr::get($r, 'strength'),
            // 'dosage'        => Arr::get($r, 'dosage'),
            // 'unit'          => Arr::get($r, 'unit'),
            // 'stock'         => Arr::get($r, 'stock'),
            // 'min_stock'     => Arr::get($r, 'min_stock'),
            // 'price_sale'    => Arr::get($r, 'price_sale'),
            // 'price_purchase'=> Arr::get($r, 'price_purchase'),
            // 'description'   => Arr::get($r, 'description'),
            // 'is_active'     => $this->boolish(Arr::get($r, 'is_active', 1)),
        ];

        // 4) Politique de persistance : upsert si sku connu, sinon create
        DB::transaction(function () use ($payload) {
            if ($this->upsertBySku && !empty($payload['sku'])) {
                $existing = ChemProduct::query()->where('sku', $payload['sku'])->first();
                if ($existing) {
                    $existing->fill($payload)->save();
                    $this->updated++;
                    return;
                }
            }

            ChemProduct::create($payload);
            $this->created++;
        });
    }

    public function rules(): array
    {
        // Validation au niveau ligne (WithHeadingRow => clés en snake_case)
        return [
            'name'          => ['required', 'string', 'max:255'],
            'generic_name'  => ['required', 'string', 'max:255'],
            'brand_name'    => ['required', 'string', 'max:255'],
            'price_ref'     => ['required', 'numeric', 'min:0'],

            // optionnels
            'category_code'   => ['nullable', 'string', 'max:255'],
            'manufacturer_name'=> ['nullable', 'string', 'max:255'],
            'form_name'        => ['nullable', 'string', 'max:255'],
            'sku'              => ['nullable', 'string', 'max:255'],
            'barcode'          => ['nullable', 'string', 'max:255'],
            'strength'         => ['nullable', 'string', 'max:255'],
            'dosage'           => ['nullable', 'string', 'max:255'],
            'unit'             => ['nullable', 'string', 'max:50'],
            'stock'            => ['nullable', 'numeric', 'min:0'],
            'min_stock'        => ['nullable', 'numeric', 'min:0'],
            'price_sale'       => ['nullable', 'numeric', 'min:0'],
            'price_purchase'   => ['nullable', 'numeric', 'min:0'],
            'description'      => ['nullable', 'string'],
            'is_active'        => ['nullable'],
        ];
    }

    public function chunkSize(): int
    {
        return 500; // optimise mémoire
    }

    public function batchSize(): int
    {
        return 500; // insert/update par lots
    }

    /** Helpers FK (lookup + cache mémoire) */

    protected function resolveCategoryId(?string $code): ?int
    {
        $code = trim((string) $code);
        if ($code === '') return null;

        if (!isset($this->cache['categories'][$code])) {
            $this->cache['categories'][$code] = ChemCategory::query()
                ->where('code', $code)
                ->value('id'); // pas de création si absent
        }

        return $this->cache['categories'][$code];
    }

    protected function resolveManufacturerId(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') return null;

        if (!isset($this->cache['manufacturers'][$name])) {
            // ici on AUTORISE la création si manquant (pratique en import)
            $this->cache['manufacturers'][$name] = ChemManufacturer::firstOrCreate(
                ['name' => $name],
                []    // autres colonnes par défaut si besoin
            )->id;
        }

        return $this->cache['manufacturers'][$name];
    }

    protected function resolveFormId(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') return null;

        if (!isset($this->cache['forms'][$name])) {
            $this->cache['forms'][$name] = ChemPharmaceuticalForm::firstOrCreate(
                ['name' => $name],
                []
            )->id;
        }

        return $this->cache['forms'][$name];
    }

    /** Convertit des valeurs "truthy" classiques en bool (1/0, oui/non, true/false) */
    protected function boolish($value): bool
    {
        if (is_bool($value)) return $value;
        $v = strtolower((string) $value);
        return in_array($v, ['1','true','yes','oui','y','on'], true);
    }
}
