<?php
namespace App\Imports;

use App\Models\ChemCategory;
use App\Models\ChemManufacturer;
use App\Models\ChemPharmaceuticalForm;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

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
        'categories'    => [],
        'manufacturers' => [],
        'forms'         => [],
    ];
    public function __construct(private ?int $actorId = null)
    {}

    private function actorId(): int
    {
        // fallback configurable si l’import tourne hors auth (queue/console)
        return $this->actorId ?? (int) config('app.system_user_id', 1);
    }
// Helpers communs (ajoute-les dans la classe)
    protected function normalizeKey(?string $v): string
    {
        // trim + minuscule + espaces multiples en un seul
        $v = trim((string) $v);
        $v = preg_replace('/\s+/', ' ', $v);
        return mb_strtolower($v);
    }

    protected function humanize(string $v): string
    {
        // transforme "antiinfl-010" -> "Antiinfl 010"
        $v = str_replace(['_', '-', '.'], ' ', $v);
        $v = preg_replace('/\s+/', ' ', trim($v));
        return mb_convert_case($v, MB_CASE_TITLE, 'UTF-8');
    }
    protected function numericOrNull($v): ?float
    {
        if ($v === null) {
            return null;
        }

        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }

        // supprime espaces (y compris insécables) & symboles monétaires
        $s = str_replace(["\xC2\xA0", ' '], '', $s);
        $s = preg_replace('/[^\d,.\-]/', '', $s);

        // s’il y a virgule ET point, on garde le dernier comme séparateur décimal
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s); // points = séparateurs de milliers
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s); // virgules = milliers
            }
        } else {
            // sinon, une virgule seule = séparateur décimal
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    public function onRow(Row $row): void
    {
        $r = $row->toArray(); // clés = entêtes normalisées (snake case)

        // 1) VALIDER les 4 obligatoires (déjà couvert par rules() + WithValidation)
        // Ici, on récupère les valeurs (trim) :
        $name        = trim((string) Arr::get($r, 'name', ''));
        $genericName = trim((string) Arr::get($r, 'generic_name', ''));
        $brandName   = trim((string) Arr::get($r, 'brand_name', ''));
        // $priceRef    = (float) Arr::get($r, 'price_ref', 0);

        $priceRef      = $this->numericOrNull(Arr::get($r, 'price_ref'));
        $priceSale     = $this->numericOrNull(Arr::get($r, 'price_sale'));
        $pricePurchase = $this->numericOrNull(Arr::get($r, 'price_purchase'));
        $stock         = $this->numericOrNull(Arr::get($r, 'stock'));
        $minStock      = $this->numericOrNull(Arr::get($r, 'min_stock'));
        // 2) Résoudre les FKs optionnelles
        $categoryId     = $this->resolveCategoryId(Arr::get($r, 'category_code'));
        $manufacturerId = $this->resolveManufacturerId(Arr::get($r, 'manufacturer_name'));
        $formId         = $this->resolveFormId(Arr::get($r, 'form_name'));

        // 3) Préparer le payload des champs
        $payload = [
            'name'            => $name,
            'generic_name'    => $genericName,
            'brand_name'      => $brandName,
            'price_ref'       => $priceRef,

            // FK optionnelles
            'category_id'     => $categoryId,
            'manufacturer_id' => $manufacturerId,
            'form_id'         => $formId,
            // 'images'          => ["products/default.jpg"],
            'created_by'      => Auth::id(),

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

        DB::transaction(function () use ($payload) {
            if ($this->upsertBySku && ! empty($payload['sku'])) {
                $existing = \App\Models\ChemProduct::where('sku', $payload['sku'])->first();
                if ($existing) {
                    $existing->fill($payload);
                    $existing->updated_by = $this->actorId();
                    $existing->save();
                    $this->updated++;
                    return;
                }
            }

            $payload['created_by'] = $this->actorId();
            $payload['updated_by'] = $this->actorId();
            \App\Models\ChemProduct::create($payload);
            $this->created++;
        });
    }

    public function rules(): array
    {

        $num = function (string $attr, $value, $fail) {
            // accepte 1,25 / 1.25 / 1 234,56 / 1.234,56 / 1 234
            $ok = $this->numericOrNull($value);
            if ($ok === null && trim((string) $value) !== '') {
                $fail('doit être un nombre (ex: 1.25 ou 1,25)');
            }
        };
        // Validation au niveau ligne (WithHeadingRow => clés en snake_case)
        return [
            'name'              => ['required', 'string', 'max:255'],
            'generic_name'      => ['required', 'string', 'max:255'],
            'brand_name'        => ['required', 'string', 'max:255'],
            'price_ref'         => ['required', $num],

            // optionnels
            'category_code'     => ['nullable', 'string', 'max:255'],
            'manufacturer_name' => ['nullable', 'string', 'max:255'],
            'form_name'         => ['nullable', 'string', 'max:255'],
            'sku'               => ['nullable', 'string', 'max:255'],
            'barcode'           => ['nullable', 'string', 'max:255'],
            'strength'          => ['nullable', 'string', 'max:255'],
            'dosage'            => ['nullable', 'string', 'max:255'],
            'unit'              => ['nullable', 'string', 'max:50'],
            'stock'             => ['nullable', $num],
            'min_stock'         => ['nullable', $num],
            'price_sale'        => ['nullable', $num],
            'price_purchase'    => ['nullable', $num],
            'description'       => ['nullable', 'string'],
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

    protected function resolveCategoryIdOld(?string $code): ?int
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        if (! isset($this->cache['categories'][$code])) {
            $this->cache['categories'][$code] = ChemCategory::query()
                ->where('name', $code)
                ->value('id'); // pas de création si absent
        }

        return $this->cache['categories'][$code];
    }
    protected function resolveCategoryId(?string $code, ?string $label = null): ?int
    {
        $raw = trim((string) $code);
        if ($raw === '') {
            return null;
        }

        $key = $this->normalizeKey($raw); // clé de cache normalisée

        if (! isset($this->cache['categories'][$key])) {
            $this->cache['categories'][$key] = \App\Models\ChemCategory::firstOrCreate(
                ['code' => $raw], // critère
                [
                    'name'       => $label ? trim($label) : $this->humanize($raw),
                    'status'     => 1,
                    'created_by' => $this->actorId(),
                    'updated_by' => $this->actorId(),
                ]
            )->id;
        }

        return $this->cache['categories'][$key];
    }

    protected function resolveManufacturerIdOld(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        if (! isset($this->cache['manufacturers'][$name])) {
            // ici on AUTORISE la création si manquant (pratique en import)
            $this->cache['manufacturers'][$name] = ChemManufacturer::firstOrCreate(
                ['name' => $name],
                []// autres colonnes par défaut si besoin
            )->id;
        }

        return $this->cache['manufacturers'][$name];
    }
    protected function resolveManufacturerId(?string $name): ?int
    {
        $raw = trim((string) $name);
        if ($raw === '') {
            return null;
        }

        $key = $this->normalizeKey($raw);

        if (! isset($this->cache['manufacturers'][$key])) {
            $this->cache['manufacturers'][$key] = \App\Models\ChemManufacturer::firstOrCreate(
                ['name' => $raw],
                [
                    'status'     => 1, // si ta table l’exige
                    'created_by' => $this->actorId(),
                    'updated_by' => $this->actorId(),
                ]
            )->id;
        }

        return $this->cache['manufacturers'][$key];
    }

    protected function resolveFormIdOld(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        if (! isset($this->cache['forms'][$name])) {
            $this->cache['forms'][$name] = ChemPharmaceuticalForm::firstOrCreate(
                ['name' => $name],
                []
            )->id;
        }

        return $this->cache['forms'][$name];
    }
    protected function resolveFormId(?string $name): ?int
    {
        $raw = trim((string) $name);
        if ($raw === '') {
            return null;
        }

        $key = $this->normalizeKey($raw);

        if (! isset($this->cache['forms'][$key])) {
            $this->cache['forms'][$key] = \App\Models\ChemPharmaceuticalForm::firstOrCreate(
                ['name' => $raw],
                [
                    'status'     => 1, // si présent
                    'created_by' => $this->actorId(),
                    'updated_by' => $this->actorId(),
                ]
            )->id;
        }

        return $this->cache['forms'][$key];
    }

    /** Convertit des valeurs "truthy" classiques en bool (1/0, oui/non, true/false) */
    protected function boolish($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $v = strtolower((string) $value);
        return in_array($v, ['1', 'true', 'yes', 'oui', 'y', 'on'], true);
    }
}
