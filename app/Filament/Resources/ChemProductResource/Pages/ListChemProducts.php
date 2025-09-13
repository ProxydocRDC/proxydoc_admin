<?php

namespace App\Filament\Resources\ChemProductResource\Pages;

use App\Filament\Resources\ChemProductResource;
use App\Models\ChemCategory;
use App\Models\ChemManufacturer;
use App\Models\ChemProduct;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ListChemProducts extends ListRecords
{
    protected static string $resource = ChemProductResource::class;

    /** En-têtes attendues dans le fichier importé */
    private const REQUIRED_HEADERS = [
        'code', 'label', 'description', 'status', 'category_code', 'manufacturer_code', 'price', 'currency',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un Produit')
                ->icon('heroicon-o-plus-circle'),

            // ---------- IMPORT ----------
            Actions\Action::make('import')
                ->label('Importer (Excel/CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Importer des produits')
                ->form([
                    FileUpload::make('file')
                        ->label('Fichier Excel ou CSV')
                        ->acceptedFileTypes([
                            'text/csv', 'text/plain', '.csv',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            '.xlsx',
                        ])
                        ->storeFiles(false)
                        ->required(),
                    Toggle::make('dry_run')
                        ->label('Mode simulation (ne rien enregistrer)')
                        ->default(false)
                        ->helperText('Permet de vérifier le fichier sans modifier la base.'),
                    Toggle::make('create_refs')
                        ->label('Créer cat./fabricants manquants')
                        ->default(false)
                        ->helperText("Si coché, crée la catégorie/fabricant par 'code' si introuvable."),
                ])
                ->extraModalFooterActions([
                    Actions\Action::make('template')
                        ->label('Télécharger le modèle CSV')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(route('chem-products.template'))
                        ->openUrlInNewTab(),
                ])
                ->action(function (array $data) {
                    $file = $data['file'] ?? null;
                    if (! $file) {
                        Notification::make()->title('Fichier manquant')->danger()->send();
                        return;
                    }
                    $reader = SimpleExcelReader::create($file->getRealPath())
    ->headersToSnakeCase();  // << rend snake_case automatiquement

$rawHeaders = $reader->getHeaders();

// normalisation supplémentaire: retire tout sauf [a-z0-9_]
$normHeaders = collect($rawHeaders)
    ->map(fn($h) => Str::of($h)
        ->lower()
        ->ascii()                       // enlève les accents (ex: catégorie -> categorie)
        ->replaceMatches('/[^a-z0-9_]+/', '_')
        ->trim('_')
        ->value()
    );

// synonymes acceptés -> clé attendue
$map = [
    'libelle'            => 'label',
    'nom'                => 'label',
    'statut'             => 'status',
    'categorie'          => 'category_code',
    'categorie_code'     => 'category_code',
    'category'           => 'category_code',
    'fabricant'          => 'manufacturer_code',
    'fabricant_code'     => 'manufacturer_code',
    'manufacturer'       => 'manufacturer_code',
    'prix'               => 'price',
    'montant'            => 'price',
    'devise'             => 'currency',
];

$headers = $normHeaders->map(fn($h) => $map[$h] ?? $h)->all();

$missing = collect(self::REQUIRED_HEADERS)->diff($headers)->values();

if ($missing->isNotEmpty()) {
    // (optionnel) utile pour diagnostiquer
    $found = implode(', ', $headers);
    Notification::make()
        ->title('En-têtes invalides')
        ->body('Colonnes manquantes : '. $missing->implode(', ')
            . "\nColonnes détectées : " . $found)
        ->danger()
        ->send();
    return;
}

                    // $reader = SimpleExcelReader::create($file->getRealPath());
                    // $headers = array_map('strtolower', $reader->getHeaders());

                    // // 1) Valider en-têtes
                    // $missing = array_diff(self::REQUIRED_HEADERS, $headers);
                    // if (! empty($missing)) {
                    //     Notification::make()
                    //         ->title('En-têtes invalides')
                    //         ->body('Colonnes manquantes : ' . implode(', ', $missing))
                    //         ->danger()
                    //         ->send();
                    //     return;
                    // }

                    $supplierId = Auth::user()?->supplier?->id; // peut être null (admin)
                    $createRefs = (bool) ($data['create_refs'] ?? false);
                    $dryRun     = (bool) ($data['dry_run'] ?? false);

                    $stats = [
                        'processed' => 0,
                        'created'   => 0,
                        'updated'   => 0,
                        'skipped'   => 0,
                        'errors'    => 0,
                    ];

                    $errors = []; // [[row, message], …]

                    // 2) Lecture + traitement
                    $rows = $reader->getRows(); // stream

                    // Optionnel : transaction (utile si pas dry-run)
                    $do = function () use ($rows, $supplierId, $createRefs, &$stats, &$errors) {
                        $rows->each(function (array $row, int $index) use (&$stats, &$errors, $supplierId, $createRefs) {
                            $stats['processed']++;

                            // Normalisation clés insensibles à la casse
                            $row = collect($row)->keyBy(fn ($v, $k) => Str::lower($k))->all();

                            // Champs minimum
                            $code  = trim((string) ($row['code'] ?? ''));
                            $label = trim((string) ($row['label'] ?? ''));

                            if ($code === '' || $label === '') {
                                $stats['skipped']++;
                                $errors[] = [$index + 2, "Ligne ignorée : 'code' ou 'label' vide."]; // +2 = header + base 1
                                return;
                            }

                            // Résolution catégorie
                            $categoryId = null;
                            if ($catCode = trim((string) ($row['category_code'] ?? ''))) {
                                $categoryId = ChemCategory::query()->where('code', $catCode)->value('id');
                                if (! $categoryId && $createRefs) {
                                    $categoryId = ChemCategory::create([
                                        'status'      => 1,
                                        'code'        => $catCode,
                                        'name'        => $row['label'] ?? $catCode,
                                        'created_by'  => Auth::id(),
                                        'updated_by'  => Auth::id(),
                                    ])->id;
                                }
                            }

                            // Résolution fabricant
                            $manufacturerId = null;
                            if ($manCode = trim((string) ($row['manufacturer_code'] ?? ''))) {
                                $manufacturerId = ChemManufacturer::query()->where('code', $manCode)->value('id');
                                if (! $manufacturerId && $createRefs) {
                                    $manufacturerId = ChemManufacturer::create([
                                        'status'      => 1,
                                        'code'        => $manCode,
                                        'name'        => $row['label'] ?? $manCode,
                                        'created_by'  => Auth::id(),
                                        'updated_by'  => Auth::id(),
                                    ])->id;
                                }
                            }

                            try {
                                /** @var ChemProduct $product */
                                $product = ChemProduct::query()->firstWhere('code', $code);

                                $payload = [
                                    'label'           => $label,
                                    'description'     => $row['description'] ?? null,
                                    'status'          => $this->normalizeStatus($row['status'] ?? 1),
                                    'supplier_id'     => $supplierId,
                                    'category_id'     => $categoryId,
                                    'manufacturer_id' => $manufacturerId,
                                    'price'           => $this->normalizeNumber($row['price'] ?? null),
                                    'currency'        => $this->normalizeCurrency($row['currency'] ?? null),
                                    'updated_by'      => Auth::id(),
                                ];

                                if ($product) {
                                    $product->fill($payload)->save();
                                    $stats['updated']++;
                                } else {
                                    $payload['code']       = $code;
                                    $payload['created_by'] = Auth::id();
                                    ChemProduct::create($payload);
                                    $stats['created']++;
                                }
                            } catch (\Throwable $e) {
                                $stats['errors']++;
                                $errors[] = [$index + 2, $e->getMessage()];
                            }
                        });
                    };

                    if ($dryRun) {
                        // Pas d'écriture : on exécute la logique sans transaction
                        $do();
                    } else {
                        DB::transaction($do);
                    }

                    // 3) Journal d’erreurs éventuel
                    $errorLink = null;
                    if (! empty($errors)) {
                        $name = 'imports/products_errors_' . now()->format('Ymd_His') . '.csv';
                        $path = 'tmp/' . $name;

                        // Sauvegarde local (disk par défaut)
                        SimpleExcelWriter::create(Storage::path($path))
                            ->addHeader(['row', 'error'])
                            ->addRows(collect($errors)->map(fn ($e) => ['row' => $e[0], 'error' => $e[1]])->all())
                            ->close();

                        $errorLink = Storage::url($path);
                    }

                    // 4) Notification de fin
                    $body = collect([
                        "Traitées : {$stats['processed']}",
                        "Créées : {$stats['created']}",
                        "Mises à jour : {$stats['updated']}",
                        "Ignorées : {$stats['skipped']}",
                        "Erreurs : {$stats['errors']}",
                        $dryRun ? 'Mode simulation : aucune donnée enregistrée.' : null,
                        $errorLink ? "Journal d’erreurs : {$errorLink}" : null,
                    ])->filter()->implode("\n");

                    Notification::make()
                        ->title('Import terminé')
                        ->body($body)
                        ->success()
                        ->send();
                }),

            // ---------- EXPORT CSV ----------
            Actions\Action::make('exportCsv')
                ->label('Exporter en CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('secondary')
                ->action(function () {
                    $supplierId = Auth::user()?->supplier?->id;
                    $query = ChemProduct::query()
                        ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId));

                    $name = 'exports/products_' . now()->format('Ymd_His') . '.csv';
                    $path = 'tmp/' . $name;

                    $writer = SimpleExcelWriter::create(Storage::path($path))
                        ->addHeader([
                            'code', 'label', 'description', 'status',
                            'category_code', 'manufacturer_code',
                            'price', 'currency',
                        ]);

                    $query->orderBy('id')
                        ->chunk(1000, function ($rows) use ($writer) {
                            $writer->addRows($rows->map(function (ChemProduct $p) {
                                return [
                                    'code'               => $p->code,
                                    'label'              => $p->label,
                                    'description'        => $p->description,
                                    'status'             => (int) $p->status,
                                    'category_code'      => optional($p->category)->code,
                                    'manufacturer_code'  => optional($p->manufacturer)->code,
                                    'price'              => $p->price,
                                    'currency'           => $p->currency,
                                ];
                            })->all());
                        });

                    $writer->close();

                    return response()->download(Storage::path($path))->deleteFileAfterSend(true);
                }),

            // ---------- EXPORT XLSX ----------
            Actions\Action::make('exportXlsx')
                ->label('Exporter en XLSX')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $supplierId = Auth::user()?->supplier?->id;
                    $query = ChemProduct::query()
                        ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId));

                    $name = 'exports/products_' . now()->format('Ymd_His') . '.xlsx';
                    $path = 'tmp/' . $name;

                    $writer = SimpleExcelWriter::create(Storage::path($path))
                        ->addHeader([
                            'code', 'label', 'description', 'status',
                            'category_code', 'manufacturer_code',
                            'price', 'currency',
                        ]);

                    $query->orderBy('id')
                        ->chunk(1000, function ($rows) use ($writer) {
                            $writer->addRows($rows->map(function (ChemProduct $p) {
                                return [
                                    'code'               => $p->code,
                                    'label'              => $p->label,
                                    'description'        => $p->description,
                                    'status'             => (int) $p->status,
                                    'category_code'      => optional($p->category)->code,
                                    'manufacturer_code'  => optional($p->manufacturer)->code,
                                    'price'              => $p->price,
                                    'currency'           => $p->currency,
                                ];
                            })->all());
                        });

                    $writer->close();

                    return response()->download(Storage::path($path))->deleteFileAfterSend(true);
                }),
        ];
    }

    // -----------------------
    // Helpers normalisation
    // -----------------------

    private function normalizeStatus($value): int
    {
        // accepte 1/0, "actif"/"inactif", "active"/"inactive", "oui"/"non"
        $v = Str::of((string) $value)->lower()->trim();
        if ($v->is('1') || $v->is('true') || $v->is('yes') || $v->is('oui') || $v->is('actif') || $v->is('active')) {
            return 1;
        }
        if ($v->is('0') || $v->is('false') || $v->is('no') || $v->is('non') || $v->is('inactif') || $v->is('inactive')) {
            return 0;
        }
        return (int) $value ?: 1;
    }

    private function normalizeNumber($value): ?float
    {
        if ($value === null || $value === '') return null;

        // tolère "1 234,56" ou "1,234.56"
        $s = Str::of((string) $value)->replace([' '], '');
        // si virgule comme décimal
        if ($s->contains(',') && !$s->contains('.')) {
            $s = $s->replace(',', '.');
        }
        return is_numeric((string) $s) ? (float) $s : null;
    }

    private function normalizeCurrency($value): ?string
    {
        $cur = Str::upper(trim((string) $value));
        if ($cur === '') return null;
        // Liste courte (adapte si besoin)
        $allowed = ['USD', 'CDF', 'EUR', 'XAF', 'XOF'];
        return in_array($cur, $allowed, true) ? $cur : 'USD';
    }
}
