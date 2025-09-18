<?php
namespace App\Imports;

use App\Models\ChemManufacturer;
use App\Models\ChemPharmacy;
use App\Models\ChemPharmacyProduct;
use App\Models\ChemProduct;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException as LvValidationException;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

class ChemPharmacyProductsImport implements
OnEachRow,
WithHeadingRow,
WithValidation,
SkipsOnFailure,
WithBatchInserts,
WithChunkReading
{
    use SkipsFailures;

    public int $created = 0;
    public int $updated = 0;

    public function __construct(private ?int $actorId = null)
    {}

    private function actorId(): int
    {
        return $this->actorId ?? 1; // fallback "system"
    }

    /* ================= Helpers ================= */

    /** 1,25 / 1.25 / 1 234,56 → float|null */
    protected function numericOrNull($v): ?float
    {
        if ($v === null) {
            return null;
        }

        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }

        $s = str_replace(["\xC2\xA0", ' '], '', $s);
        $s = preg_replace('/[^\d,.\-]/', '', $s);

        if (str_contains($s, ',') && str_contains($s, '.')) {
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } else {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    /** yyyy-mm-dd | dd/mm/yyyy | mm/dd/yyyy → Y-m-d|null */
    protected function parseDate($v): ?string
    {
        if (! $v) {
            return null;
        }

        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }

        // déjà Y-m-d ?
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $s;
        }

        // d/m/Y ou m/d/Y
        $parts = preg_split('#[/-]#', $s);
        if (count($parts) === 3) {
            [$a, $b, $c] = $parts;
            if (strlen($c) === 4) {
                // d/m/Y ou m/d/Y : tente d'abord d/m/Y, sinon m/d/Y
                if (checkdate((int) $b, (int) $a, (int) $c)) {
                    return sprintf('%04d-%02d-%02d', $c, $b, $a);
                }
                if (checkdate((int) $a, (int) $b, (int) $c)) {
                    return sprintf('%04d-%02d-%02d', $c, $a, $b);
                }
            }
        }
        return null;
    }

    /** USD / CDF (accepte usd/cdf/$/fc/rdc…) */
    protected function normalizeCurrency($v): ?string
    {
        $s = strtoupper(trim((string) $v));
        if ($s === 'USD' || $s === '$') {
            return 'USD';
        }

        if (in_array($s, ['CDF', 'FC', 'CDFR', 'RDC', 'CDFONGO'])) {
            return 'CDF';
        }

        return null;
    }

    /** clé S3 à partir d’une URL complète ou déjà clé */
    protected function s3KeyFromUrlOrKey(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $value)) {
            $key    = ltrim($value, '/');
            $bucket = config('filesystems.disks.s3.bucket');
            if ($bucket && str_starts_with($key, $bucket . '/')) {
                $key = substr($key, strlen($bucket) + 1);
            }
            return $key ?: null;
        }

        $parts = parse_url($value);
        $path  = isset($parts['path']) ? ltrim($parts['path'], '/') : null;
        if (! $path) {
            return null;
        }

        $bucket = config('filesystems.disks.s3.bucket');
        if ($bucket && str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }
        return $path ?: null;
    }

    /* ============== Résolution des FK ============== */

    protected function resolvePharmacyId($idOrName): ?int
    {
        if (blank($idOrName)) {
            return null;
        }

        if (is_numeric($idOrName)) {
            return ChemPharmacy::whereKey((int) $idOrName)->value('id');
        }
        $name = trim((string) $idOrName);
        return ChemPharmacy::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->value('id');
    }

    protected function resolveProductId($idOrSkuOrName): ?int
    {
        if (blank($idOrSkuOrName)) {
            return null;
        }

        if (is_numeric($idOrSkuOrName)) {
            return ChemProduct::whereKey((int) $idOrSkuOrName)->value('id');
        }

        $v = trim((string) $idOrSkuOrName);

        // SKU prioritaire si fourni dans "product_sku"
        if (str_contains($v, 'SKU:')) {
            $sku = trim(str_replace('SKU:', '', $v));
            return ChemProduct::where('sku', $sku)->value('id');
        }

        // sinon on essaie par SKU explicite, sinon par noms
        return ChemProduct::query()
            ->where('sku', $v)
            ->orWhereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($v)])
            ->orWhereRaw('LOWER(TRIM(brand_name)) = ?', [mb_strtolower($v)])
            ->orWhereRaw('LOWER(TRIM(generic_name)) = ?', [mb_strtolower($v)])
            ->value('id');
    }

    protected function resolveManufacturerId($idOrName): ?int
    {
        if (blank($idOrName)) {
            return null;
        }

        if (is_numeric($idOrName)) {
            return ChemManufacturer::whereKey((int) $idOrName)->value('id');
        }
        $name = trim((string) $idOrName);
        // Ici on ne crée PAS (champ nullable). Si tu veux créer: décommente le firstOrCreate.
        return ChemManufacturer::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->value('id');

        // return ChemManufacturer::firstOrCreate(['name'=>$name], [
        //     'status'=>1,'created_by'=>$this->actorId(),'updated_by'=>$this->actorId(),
        // ])->id;
    }

    /* ============== Validation ============== */

    public function rules(): array
    {
        $num = function (string $attr, $value, $fail) {
            $ok = $this->numericOrNull($value);
            if ($ok === null && trim((string) $value) !== '') {
                $fail('doit être un nombre (ex: 1.25 ou 1,25)');
            }
        };

        return [
            // Required logiques (on contrôlera présence d’IDs résolus dans onRow)
            'pharmacy_id'       => ['nullable'],
            'pharmacy_name'     => ['nullable', 'string', 'max:256'],

            'product_id'        => ['nullable'],
            'product_sku'       => ['nullable', 'string', 'max:255'],
            'product_name'      => ['nullable', 'string', 'max:256'],

            'manufacturer_id'   => ['nullable'],
            'manufacturer_name' => ['nullable', 'string', 'max:255'],

            'sale_price'        => ['required', $num],
            'cost_price'        => ['nullable', $num],
            'stock_qty'         => ['nullable', $num],
            'reorder_level'     => ['nullable', 'integer'],

            'currency'          => ['required', 'string', 'max:3'],
            'expiry_date'       => ['nullable', 'string', 'max:20'],
            'origin_country'    => ['nullable', 'string', 'max:3'],
            'lot_ref'           => ['nullable', 'string', 'max:50'],
            'image'             => ['nullable', 'string', 'max:1000'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'status'            => ['nullable', 'integer'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'sale_price.required' => 'La colonne "sale_price" est obligatoire.',
            'currency.required'   => 'La colonne "currency" est obligatoire (USD/CDF).',
        ];
    }

    public function chunkSize(): int
    {return 500;}
    public function batchSize(): int
    {return 500;}

    /* ============== Traitement ligne ============== */

    public function onRow(Row $row): void
    {
        $r = $row->toArray();

        // Résolutions
        $pharmacyId = $this->resolvePharmacyId(
            Arr::get($r, 'pharmacy_id') ?? Arr::get($r, 'pharmacy_name')
        );
        $productId = $this->resolveProductId(
            Arr::get($r, 'product_id') ?? Arr::get($r, 'product_sku') ?? Arr::get($r, 'product_name')
        );
        $manufacturerId = $this->resolveManufacturerId(
            Arr::get($r, 'manufacturer_id') ?? Arr::get($r, 'manufacturer_name')
        );

        // Contrôles applicatifs
        $errors = [];
        if (empty($pharmacyId)) {
            $errors['pharmacy'] = ['Pharmacie introuvable (pharmacy_id/pharmacy_name).'];
        }

        if (empty($productId)) {
            $errors['product'] = ['Produit introuvable (product_id/product_sku/product_name).'];
        }

        $currency = $this->normalizeCurrency(Arr::get($r, 'currency'));
        if (! $currency) {
            $errors['currency'] = ['Devise invalide (USD/CDF attendue).'];
        }
if ($errors) {
    $this->failRow($row, $errors);
}

        // Conversions
        $salePrice  = $this->numericOrNull(Arr::get($r, 'sale_price'));
        $costPrice  = $this->numericOrNull(Arr::get($r, 'cost_price'));
        $stockQty   = $this->numericOrNull(Arr::get($r, 'stock_qty')) ?? 0.0;
        $expiryDate = $this->parseDate(Arr::get($r, 'expiry_date'));

        $payload = [
            'status'          => (int) (Arr::get($r, 'status', 1) ?: 1),
            'pharmacy_id'     => (int) $pharmacyId,
            'product_id'      => (int) $productId,
            'manufacturer_id' => $manufacturerId ? (int) $manufacturerId : null,
            'lot_ref'         => Arr::get($r, 'lot_ref'),
            'origin_country'  => strtoupper((string) Arr::get($r, 'origin_country', '')) ?: null,
            'expiry_date'     => $expiryDate,
            'cost_price'      => $costPrice,
            'sale_price'      => $salePrice,
            'currency'        => $currency,
            'stock_qty'       => $stockQty,
            'reorder_level'   => Arr::get($r, 'reorder_level'),
            'image'           => $this->s3KeyFromUrlOrKey(Arr::get($r, 'image')),
            'description'     => Arr::get($r, 'description'),
        ];

        // Upsert: (pharmacy_id, product_id, lot_ref?) comme clé logique
        DB::transaction(function () use ($payload) {
            $q = ChemPharmacyProduct::query()
                ->where('pharmacy_id', $payload['pharmacy_id'])
                ->where('product_id', $payload['product_id']);

            if (! empty($payload['lot_ref'])) {
                $q->where('lot_ref', $payload['lot_ref']);
            }

            $existing = $q->first();

            if ($existing) {
                $existing->fill($payload);
                $existing->updated_by = $this->actorId();
                $existing->save();
                $this->updated++;
            } else {
                $payload['created_by'] = $this->actorId();
                $payload['updated_by'] = $this->actorId();
                ChemPharmacyProduct::create($payload);
                $this->created++;
            }
        });
    }
     private function failRow(Row $row, array $messages): never
    {
        throw new ExcelValidationException(
            LvValidationException::withMessages($messages),
            $row
        );
    }
}
