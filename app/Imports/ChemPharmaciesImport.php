<?php

namespace App\Imports;

use Maatwebsite\Excel\Row;
use Illuminate\Support\Arr;
use App\Models\ChemPharmacy;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Models\User;               // adapte si ton modèle user diffère
use App\Models\ChemSupplier;       // adapte le nom du modèle fournisseur
// use Illuminate\Validation\ValidationException as LaravelValidationException;

class ChemPharmaciesImport implements
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

    public function __construct(private ?int $actorId = null) {}

    private function actorId(): int
    {
        return $this->actorId ?? 1; // fallback "system"
    }

    /** -------- Helpers généraux -------- */

    protected function normalizeKey(?string $v): string
    {
        $v = trim((string) $v);
        $v = preg_replace('/\s+/', ' ', $v);
        return mb_strtolower($v);
    }

    // Convertit URL S3 complète -> "pharmacies/file.png", ou laisse la clé telle quelle
    protected function s3KeyFromUrlOrKey(?string $value): ?string
    {
        if (!is_string($value) || $value === '') return null;

        // déjà une clé ?
        if (!preg_match('#^https?://#i', $value)) {
            $key = ltrim($value, '/');
            $bucket = config('filesystems.disks.s3.bucket');
            if ($bucket && str_starts_with($key, $bucket.'/')) {
                $key = substr($key, strlen($bucket) + 1);
            }
            return $key ?: null;
        }

        // URL complète -> extraire le path
        $parts = parse_url($value);
        $path  = isset($parts['path']) ? ltrim($parts['path'], '/') : null;
        if (!$path) return null;

        $bucket = config('filesystems.disks.s3.bucket');
        if ($bucket && str_starts_with($path, $bucket.'/')) {
            $path = substr($path, strlen($bucket) + 1);
        }
        return $path ?: null;
    }

    /** -------- Résolutions d’IDs -------- */

    protected function resolveSupplierId($idOrName): ?int
    {
        if (blank($idOrName)) return null;

        // si numérique -> id direct
        if (is_numeric($idOrName)) {
            return ChemSupplier::whereKey((int)$idOrName)->value('id');
        }

        // sinon par nom (insensible à la casse/espaces)
        $name = trim((string) $idOrName);
        return ChemSupplier::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->value('id');
    }

    protected function resolveUserId($idOrEmailOrName): ?int
    {
        if (blank($idOrEmailOrName)) return null;

        // id numérique
        if (is_numeric($idOrEmailOrName)) {
            return User::whereKey((int)$idOrEmailOrName)->value('id');
        }

        $v = trim((string) $idOrEmailOrName);

        // email
        if (filter_var($v, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $v)->value('id');
        }

        // nom (champ "name") — adapte selon ton schéma si firstname/lastname
        return User::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($v)])
            ->value('id');
    }

    /** -------- Validation -------- */

    public function rules(): array
    {
        // On accepte "supplier_id" OU "supplier_name" (au moins un des deux)
        // et "user_id" OU "user_email" OU "user_name" (au moins un des trois)
        return [
            'name'           => ['required', 'string', 'max:256'],
            'zone_id'        => ['required', 'integer'],    // à adapter si lookup par nom de zone
            'status'         => ['nullable', 'integer'],

            'supplier_id'    => ['nullable', 'integer'],
            'supplier_name'  => ['nullable', 'string', 'max:255'],
            'user_id'        => ['nullable', 'integer'],
            'user_email'     => ['nullable', 'email'],
            'user_name'      => ['nullable', 'string', 'max:255'],

            'phone'          => ['nullable', 'string', 'max:20'],
            'email'          => ['nullable', 'email', 'max:256'],
            'address'        => ['nullable', 'string', 'max:500'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'logo'           => ['nullable', 'string', 'max:1000'], // URL ou clé, on convertira
            'rating'         => ['nullable', 'numeric'],
            'nb_review'      => ['nullable', 'integer'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'name.required'    => 'La colonne "name" est obligatoire.',
            'zone_id.required' => 'La colonne "zone_id" est obligatoire.',
        ];
    }

    public function chunkSize(): int { return 500; }
    public function batchSize(): int { return 500; }

    /** -------- Traitement ligne par ligne -------- */

    public function onRow(Row $row): void
    {
        $r = $row->toArray();

        // Résoudre supplier & user
        $supplierId = $this->resolveSupplierId(
            Arr::get($r, 'supplier_id') ?? Arr::get($r, 'supplier_name')
        );
        $userId = $this->resolveUserId(
            Arr::get($r, 'user_id') ?? Arr::get($r, 'user_email') ?? Arr::get($r, 'user_name')
        );

        // champs NOT NULL de ton schéma
        $zoneId  = Arr::get($r, 'zone_id');
        $status  = (int) (Arr::get($r, 'status', 1) ?: 1);

        // Contrôles applicatifs avant insert
        $errors = [];
        if (empty($supplierId)) $errors['supplier'] = ['Fournisseur introuvable (supplier_id / supplier_name).'];
        if (empty($userId))     $errors['user']     = ['Utilisateur introuvable (user_id / user_email / user_name).'];

        if ($errors) {
            // marque cette ligne en échec lisible
            throw new ValidationException(ValidationException::withMessages($errors));
        }

        $payload = [
            'status'      => $status,
            'name'        => Arr::get($r, 'name'),
            'phone'       => Arr::get($r, 'phone'),
            'email'       => Arr::get($r, 'email'),
            'address'     => Arr::get($r, 'address'),
            'description' => Arr::get($r, 'description'),
            'logo'        => $this->s3KeyFromUrlOrKey(Arr::get($r, 'logo')),
            'zone_id'     => (int) $zoneId,
            'rating'      => Arr::get($r, 'rating'),
            'nb_review'   => Arr::get($r, 'nb_review'),

            'supplier_id' => (int) $supplierId,
            'user_id'     => (int) $userId,
        ];

        DB::transaction(function () use ($payload) {
            // Upsert possible si tu as une clé "name + supplier_id" unique ; sinon create simple
            ChemPharmacy::create(array_merge($payload, [
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ]));
            $this->created++;
        });
    }
}
