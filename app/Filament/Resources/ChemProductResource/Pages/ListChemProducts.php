<?php
namespace App\Filament\Resources\ChemProductResource\Pages;

use App\Filament\Imports\ChemProductImporter;
use App\Filament\Resources\ChemProductResource;
use App\Models\ChemProduct;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Actions\Imports\Models\Import;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ListChemProducts extends ListRecords
{
    protected static string $resource = ChemProductResource::class;

    public string $viewMode = 'table'; // 'table' ou 'grid'

    protected function getHeaderWidgets(): array
    {
        return [];
    }


    public function getView(): string
    {
        if ($this->viewMode === 'grid') {
            return 'filament.resources.chem-product-resource.pages.list-chem-products-grid';
        }
        return parent::getView();
    }

    public function getTableFilterState(?string $name = null): ?array
    {
        $state = parent::getTableFilterState($name);
        
        // Si un nom de filtre spécifique est demandé
        if ($name !== null) {
            // S'assurer que c'est toujours un array ou null
            if (is_string($state)) {
                // Si c'est une string, c'est probablement pour has_image
                if ($name === 'has_image') {
                    if ($state === 'true' || $state === '1') {
                        return ['value' => true];
                    } elseif ($state === 'false' || $state === '0') {
                        return ['value' => false];
                    }
                    return null;
                }
                return null;
            }
            return is_array($state) ? $state : null;
        }
        
        // Si aucun nom spécifique, retourner tous les filtres
        // S'assurer que $state est toujours un array
        if (!is_array($state)) {
            $state = [];
        }
        
        // Corriger le filtre has_image qui peut être une string au lieu d'un array
        // TernaryFilter attend un array avec 'value' => true/false/null
        if (isset($state['has_image'])) {
            if (is_string($state['has_image'])) {
                if ($state['has_image'] === 'true' || $state['has_image'] === '1') {
                    $state['has_image'] = ['value' => true];
                } elseif ($state['has_image'] === 'false' || $state['has_image'] === '0') {
                    $state['has_image'] = ['value' => false];
                } else {
                    $state['has_image'] = null;
                }
            } elseif (!is_array($state['has_image'])) {
                // Si ce n'est pas un array, on le convertit ou on le supprime
                if (is_bool($state['has_image'])) {
                    $state['has_image'] = ['value' => $state['has_image']];
                } else {
                    unset($state['has_image']);
                }
            } elseif (is_array($state['has_image']) && !isset($state['has_image']['value'])) {
                // Si c'est un array mais sans 'value', on le supprime
                unset($state['has_image']);
            }
        }
        
        return $state;
    }

    public function updateStatus($productId, $status)
    {
        $product = ChemProduct::find($productId);
        if ($product) {
            $product->status = $status;
            $product->save();
            
            Notification::make()
                ->title('Statut mis à jour')
                ->body("Le produit « {$product->name} » est maintenant " . ($status == 1 ? 'Actif' : 'Inactif') . '.')
                ->success()
                ->send();
        }
    }

    public function togglePrescription($productId)
    {
        $product = ChemProduct::find($productId);
        if ($product) {
            $product->with_prescription = (int) ! (bool) $product->with_prescription;
            $product->save();
            
            Notification::make()
                ->title('Statut mis à jour')
                ->body(
                    $product->with_prescription
                    ? 'Ordonnance requise: OUI.'
                    : 'Ordonnance requise: NON.'
                )
                ->success()
                ->send();
        }
    }

    public function clearImages($productId, $deleteS3 = false)
    {
        $product = ChemProduct::find($productId);
        if ($product && !empty($product->images)) {
            $keys = is_array($product->images) ? $product->images : [];
            $deleted = 0;

            if ($deleteS3 && $keys) {
                $disk = Storage::disk('s3');
                foreach ($keys as $k) {
                    $key = preg_match('#^https?://#i', (string) $k)
                        ? ltrim(parse_url($k, PHP_URL_PATH) ?? '', '/')
                        : ltrim((string) $k, '/');

                    $bucket = config('filesystems.disks.s3.bucket');
                    if ($bucket && Str::startsWith($key, $bucket . '/')) {
                        $key = substr($key, strlen($bucket) + 1);
                    }

                    try {
                        if ($key) {
                            $disk->delete($key);
                            $deleted++;
                        }
                    } catch (\Throwable $e) {
                        // continue
                    }
                }
            }

            $product->images = [];
            $product->save();
            
            Notification::make()
                ->title('Images vidées')
                ->body(($deleted ? "Fichiers S3 supprimés: {$deleted}. " : '') . 'La colonne "images" est maintenant vide.')
                ->success()
                ->send();
        }
    }

    // Méthodes spécifiques pour la vue grille - utilisent les actions Filament de la vue tableau
    public function viewImagesGrid($productId, $productName)
    {
        // Utiliser l'action Filament de la vue tableau
        $this->mountTableAction('viewImages', $productId);
    }

    public function clearImagesGrid($productId)
    {
        // Utiliser l'action Filament de la vue tableau
        $this->mountTableAction('clearImages', $productId);
    }

    public function viewDetailsGrid($productId, $productName)
    {
        // Utiliser l'action Filament de la vue tableau
        $this->mountTableAction('viewDetails', $productId);
    }

    public function delete($productId)
    {
        $product = ChemProduct::find($productId);
        if ($product) {
            $name = $product->name;
            $product->delete();
            
            Notification::make()
                ->title('Produit supprimé')
                ->body("Le produit « {$name} » a été supprimé.")
                ->success()
                ->send();
        }
    }

    public function exportCsv()
    {
        $supplierId = Auth::user()?->supplier?->id;
        $query      = ChemProduct::query()
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId));

        $disk = Storage::disk('local');
        $dir  = 'tmp';
        $disk->makeDirectory($dir);

        $name = 'exports/products_' . now()->format('Ymd_His') . '.csv';
        $path = $disk->path($dir . '/' . basename($name));

        $writer = SimpleExcelWriter::create($path)->addHeader([
            'name', 'generic_name', 'brand_name',
            'category_code', 'manufacturer_code', 'form_name',
            'strength', 'unit', 'packaging', 'atc_code',
            'price_ref', 'with_prescription', 'shelf_life_months',
            'indications', 'contraindications', 'side_effects',
            'storage_conditions', 'images', 'description', 'composition',
        ]);

        $query->orderBy('id')->chunk(1000, function ($rows) use ($writer) {
            $writer->addRows($rows->map(function (ChemProduct $p) {
                return [
                    'name'               => $p->name,
                    'generic_name'       => $p->generic_name,
                    'brand_name'         => $p->brand_name,
                    'category_code'      => optional($p->category)->code,
                    'manufacturer_code'  => optional($p->manufacturer)->code,
                    'form_name'          => optional($p->form)->name,
                    'strength'           => $p->strength,
                    'unit'               => $p->unit,
                    'packaging'          => $p->packaging,
                    'atc_code'           => $p->atc_code,
                    'price_ref'          => $p->price_ref,
                    'with_prescription'  => $p->with_prescription ? 1 : 0,
                    'shelf_life_months'  => $p->shelf_life_months,
                    'indications'        => $p->indications,
                    'contraindications'  => $p->contraindications,
                    'side_effects'       => $p->side_effects,
                    'storage_conditions' => $p->storage_conditions,
                    'images'             => is_array($p->images) ? implode('; ', $p->images) : (string) $p->images,
                    'description'        => $p->description,
                    'composition'        => $p->composition,
                ];
            })->all());
        });

        $writer->close();

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function exportXlsx()
    {
        $supplierId = Auth::user()?->supplier?->id;
        $query      = ChemProduct::query()
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId));

        $disk = Storage::disk('local');
        $dir  = 'tmp';
        $disk->makeDirectory($dir);

        $name = 'exports/products_' . now()->format('Ymd_His') . '.xlsx';
        $path = $disk->path($dir . '/' . basename($name));

        $writer = SimpleExcelWriter::create($path)->addHeader([
            'name', 'generic_name', 'brand_name',
            'category_code', 'manufacturer_code', 'form_name',
            'strength', 'unit', 'packaging', 'atc_code',
            'price_ref', 'with_prescription', 'shelf_life_months',
            'indications', 'contraindications', 'side_effects',
            'storage_conditions', 'images', 'description', 'composition',
        ]);

        $query->orderBy('id')->chunk(1000, function ($rows) use ($writer) {
            $writer->addRows($rows->map(function (ChemProduct $p) {
                return [
                    'name'               => $p->name,
                    'generic_name'       => $p->generic_name,
                    'brand_name'         => $p->brand_name,
                    'category_code'      => optional($p->category)->code,
                    'manufacturer_code'  => optional($p->manufacturer)->code,
                    'form_name'          => optional($p->form)->name,
                    'strength'           => $p->strength,
                    'unit'               => $p->unit,
                    'packaging'          => $p->packaging,
                    'atc_code'           => $p->atc_code,
                    'price_ref'          => $p->price_ref,
                    'with_prescription'  => $p->with_prescription ? 1 : 0,
                    'shelf_life_months'  => $p->shelf_life_months,
                    'indications'        => $p->indications,
                    'contraindications'  => $p->contraindications,
                    'side_effects'       => $p->side_effects,
                    'storage_conditions' => $p->storage_conditions,
                    'images'             => is_array($p->images) ? implode('; ', $p->images) : (string) $p->images,
                    'description'        => $p->description,
                    'composition'        => $p->composition,
                ];
            })->all());
        });

        $writer->close();

        return response()->download($path)->deleteFileAfterSend(true);
    }

    /** En-têtes attendues dans le fichier importé */
    public const REQUIRED_HEADERS = [
        'code', 'label', 'description', 'status', 'category_code', 'manufacturer_code', 'price', 'currency',
    ];

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Ajouter un message informatif si une plage de dates est sélectionnée
        try {
            $dateFilter = $this->tableFilters['created_at'] ?? null;
            $dateFrom = is_array($dateFilter) ? ($dateFilter['from'] ?? null) : null;
            $dateTo = is_array($dateFilter) ? ($dateFilter['to'] ?? null) : null;

            if ($dateFrom || $dateTo) {
                $fromText = $dateFrom ? Carbon::parse($dateFrom)->format('d/m/Y') : 'Début';
                $toText = $dateTo ? Carbon::parse($dateTo)->format('d/m/Y') : 'Fin';
                
                // Ajouter une action informative (non cliquable)
                $actions[] = Actions\Action::make('dateRangeInfo')
                    ->label("Période : {$fromText} - {$toText}")
                    ->icon('heroicon-o-calendar')
                    ->color('info')
                    ->disabled()
                    ->extraAttributes(['class' => 'cursor-default opacity-100']);
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs si les filtres ne sont pas encore disponibles
        }
        
        // Ajouter les actions existantes
        $actions = array_merge($actions, [
            Actions\Action::make('toggleView')
                ->label(fn() => $this->viewMode === 'table' ? 'Vue grille' : 'Vue tableau')
                ->icon(fn() => $this->viewMode === 'table' ? 'heroicon-o-squares-2x2' : 'heroicon-o-table-cells')
                ->color('gray')->hidden()
                ->action(function () {
                    $this->viewMode = $this->viewMode === 'table' ? 'grid' : 'table';
                }),
            Actions\CreateAction::make()
                ->label('Ajouter un produit')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),
            Actions\Action::make('downloadTemplate')
                ->label('Télécharger le modèle')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('danger')
                ->url(fn() => route('products.template'))
                ->openUrlInNewTab()
                ->tooltip('Modèle avec en-têtes (et exemple) pour l\'import des produits'),
            Actions\Action::make('importProducts')
                ->label('Importer')
                ->icon('heroicon-m-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Fichier CSV/XLSX')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->preserveFilenames()
                        ->downloadable()
                        ->helperText('Utilise des entêtes : name, generic_name, brand_name, price_ref (obligatoires). Optionnels : category_code, manufacturer_name, form_name, sku, barcode, strength, dosage, unit, stock, min_stock, price_sale, price_purchase, description, is_active.'),
                ])
                ->action(function (array $data) {
                    $path = $data['file'] ?? null;
                    if (!$path || !Storage::disk('local')->exists($path)) {
                        Notification::make()->title('Fichier introuvable')->danger()->send();
                        return;
                    }
                    // Utiliser l'importeur existant
                    try {
                        $importer = new \App\Imports\ChemProductsImport();
                        \Maatwebsite\Excel\Facades\Excel::import($importer, Storage::disk('local')->path($path));
                        Notification::make()
                            ->title('Import terminé')
                            ->body("Lignes importées : " . ($importer->created ?? 0))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur lors de l\'import')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            // 🔽 Import Filament, avec notre importeur + bouton "Modèle" dans le modal
            // ImportAction::make()
            //     ->label('Importer (Excel/CSV)')
            //     ->icon('heroicon-o-arrow-up-tray')
            //     ->importer(ChemProductImporter::class)
            //     ->chunkSize(1000)
            // // Supprime la phrase par défaut du modal (on garde uniquement le bouton)
            //     ->modalDescription('')
            // // Ajoute le bouton "Modèle lisible" *dans* le footer du modal
            //     ->extraModalFooterActions([
            //         $this->makeTemplateCsvAction(),
            //         // 👉 si tu veux aussi un modèle XLSX, décommente la ligne suivante
            //         $this->makeTemplateXlsxAction(),
            //     ])->after(function ($livewire, Import $import) {
            //     $ok = $import->successful_rows ?? ($import->successfulRows ?? 0);
            //     $ko = method_exists($import, 'getFailedRowsCount')
            //         ? $import->getFailedRowsCount()
            //         : ($import->failed_rows ?? ($import->failedRows ?? 0));

            //     Notification::make()
            //         ->title('Import produits terminé')
            //         ->body("Lignes OK : {$ok} • Erreurs : {$ko}")
            //         ->icon('heroicon-o-check-circle')
            //         ->success()
            //         // ->actions([
            //         //     Notification::make('voir')
            //         //         ->button()
            //         //         ->url(\App\Filament\Resources\ChemProductResource::getUrl('index'), true),
            //         // ])
            //         ->sendToDatabase(Auth::user()); // ← persistant (cloche)
            // }),

            // Grouper les exports dans un seul menu “Exporter”
            Actions\ActionGroup::make([

                Actions\Action::make('exportCsv')
                    ->label('CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $supplierId = Auth::user()?->supplier?->id;
                        $query      = ChemProduct::query()
                            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId));

                        $disk = Storage::disk('local');
                        $dir  = 'tmp';
                        $disk->makeDirectory($dir);

                        $name = 'exports/products_' . now()->format('Ymd_His') . '.csv';
                        $path = $disk->path($dir . '/' . basename($name));

                        $writer = SimpleExcelWriter::create($path)->addHeader([
                            'name', 'generic_name', 'brand_name',
                            'category_code', 'manufacturer_code', 'form_name',
                            'strength', 'unit', 'packaging', 'atc_code',
                            'price_ref', 'with_prescription', 'shelf_life_months',
                            'indications', 'contraindications', 'side_effects',
                            'storage_conditions', 'images', 'description', 'composition',
                        ]);

                        $query->orderBy('id')->chunk(1000, function ($rows) use ($writer) {
                            $writer->addRows($rows->map(function (ChemProduct $p) {
                                return [
                                    'name'               => $p->name,
                                    'generic_name'       => $p->generic_name,
                                    'brand_name'         => $p->brand_name,
                                    'category_code'      => optional($p->category)->code,
                                    'manufacturer_code'  => optional($p->manufacturer)->code,
                                    'form_name'          => optional($p->form)->name,
                                    'strength'           => $p->strength,
                                    'unit'               => $p->unit,
                                    'packaging'          => $p->packaging,
                                    'atc_code'           => $p->atc_code,
                                    'price_ref'          => $p->price_ref,
                                    'with_prescription'  => $p->with_prescription ? 1 : 0,
                                    'shelf_life_months'  => $p->shelf_life_months,
                                    'indications'        => $p->indications,
                                    'contraindications'  => $p->contraindications,
                                    'side_effects'       => $p->side_effects,
                                    'storage_conditions' => $p->storage_conditions,
                                    // images stockées en array JSON → on exporte une liste “; ”
                                    'images'             => is_array($p->images) ? implode('; ', $p->images) : (string) $p->images,
                                    'description'        => $p->description,
                                    'composition'        => $p->composition,
                                ];
                            })->all());
                        });

                        $writer->close();

                        return response()->download($path)->deleteFileAfterSend(true);
                    }),

                Actions\Action::make('exportXlsx')
                    ->label('XLSX')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('secondary')
                    ->action(function () {
                        $supplierId = Auth::user()?->supplier?->id;
                        $query      = ChemProduct::query()
                            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId));

                        $disk = Storage::disk('local');
                        $dir  = 'tmp';
                        $disk->makeDirectory($dir);

                        $name = 'exports/products_' . now()->format('Ymd_His') . '.xlsx';
                        $path = $disk->path($dir . '/' . basename($name));

                        $writer = SimpleExcelWriter::create($path)->addHeader([
                            'name', 'generic_name', 'brand_name',
                            'category_code', 'manufacturer_code', 'form_name',
                            'strength', 'unit', 'packaging', 'atc_code',
                            'price_ref', 'with_prescription', 'shelf_life_months',
                            'indications', 'contraindications', 'side_effects',
                            'storage_conditions', 'images', 'description', 'composition',
                        ]);

                        $query->orderBy('id')->chunk(1000, function ($rows) use ($writer) {
                            $writer->addRows($rows->map(function (ChemProduct $p) {
                                return [
                                    'name'               => $p->name,
                                    'generic_name'       => $p->generic_name,
                                    'brand_name'         => $p->brand_name,
                                    'category_code'      => optional($p->category)->code,
                                    'manufacturer_code'  => optional($p->manufacturer)->code,
                                    'form_name'          => optional($p->form)->name,
                                    'strength'           => $p->strength,
                                    'unit'               => $p->unit,
                                    'packaging'          => $p->packaging,
                                    'atc_code'           => $p->atc_code,
                                    'price_ref'          => $p->price_ref,
                                    'with_prescription'  => $p->with_prescription ? 1 : 0,
                                    'shelf_life_months'  => $p->shelf_life_months,
                                    'indications'        => $p->indications,
                                    'contraindications'  => $p->contraindications,
                                    'side_effects'       => $p->side_effects,
                                    'storage_conditions' => $p->storage_conditions,
                                    'images'             => is_array($p->images) ? implode('; ', $p->images) : (string) $p->images,
                                    'description'        => $p->description,
                                    'composition'        => $p->composition,
                                ];
                            })->all());
                        });

                        $writer->close();

                        return response()->download($path)->deleteFileAfterSend(true);
                    }),
            ])
                ->label('Exporter')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->button(),
        ]);
        
        return $actions;
    }
    /**
     * Bouton "Modèle (en-têtes lisibles)" — CSV
     * Génère un fichier CSV avec des en-têtes compréhensibles + une ligne d’exemple.
     */
    private function makeTemplateCsvAction(): Actions\Action
    {
        return Actions\Action::make('template_friendly_csv')
            ->label('Modèle (en-têtes lisibles)')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success') // joli bouton vert
            ->outlined()       // style outline
            ->action(function () {
                // En-têtes lisibles (les users mapperont ces colonnes dans le modal d’import)
                $headers = [
                    'Nom commercial', 'Nom générique', 'Marque', 'Prix de référence (USD)',
                    'Code catégorie (facultatif)', 'Nom du fabricant (facultatif)', 'Forme galénique (facultatif)',
                    'Dosage', 'Unité', 'Conditionnement', 'Code ATC',
                    'Avec ordonnance ?', 'Durée de conservation (mois)',
                    'Indications', 'Contre-indications', 'Effets secondaires',
                    'Conditions de stockage', 'Images (liste ou JSON)', 'Description', 'Composition',
                ];

                // Une ligne d’exemple pour guider
                $sample = [[
                    'Doliprane 500mg', 'Paracétamol', 'Doliprane', '2.5',
                    'ANALG', 'SANOFI', 'Comprimé',
                    '500', 'mg', 'Boîte de 16 cp', 'N02BE',
                    '0', '24',
                    'Douleur, fièvre', 'Insuffisance hépatique', 'Nausées, éruption',
                    '15–25 °C', 'products/p1.jpg; products/p1b.jpg',
                    'Antalgique', 'Paracétamol 500mg',
                ]];

                // Écrit dans storage/app/tmp/…
                $disk = Storage::disk('local');
                $dir  = 'tmp';
                $disk->makeDirectory($dir);
                $path = $disk->path("$dir/modele_produits_lisible.csv");

                SimpleExcelWriter::create($path)
                    ->addHeader($headers)
                    ->addRows($sample)
                    ->close();

                return response()->download($path)->deleteFileAfterSend(true);
            });
    }

    /**
     * (Optionnel) Bouton "Modèle XLSX"
     */
    private function makeTemplateXlsxAction(): Actions\Action
    {
        return Actions\Action::make('template_friendly_xlsx')
            ->label('Modèle XLSX')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->outlined()
            ->action(function () {
                $headers = [
                    'Nom commercial', 'Nom générique', 'Marque', 'Prix de référence (USD)',
                    'Code catégorie (facultatif)', 'Nom du fabricant (facultatif)', 'Forme galénique (facultatif)',
                    'Dosage', 'Unité', 'Conditionnement', 'Code ATC',
                    'Avec ordonnance ?', 'Durée de conservation (mois)',
                    'Indications', 'Contre-indications', 'Effets secondaires',
                    'Conditions de stockage', 'Images (liste ou JSON)', 'Description', 'Composition',
                ];
                $sample = [[
                    'Doliprane 500mg', 'Paracétamol', 'Doliprane', '2.5',
                    'ANALG', 'SANOFI', 'Comprimé',
                    '500', 'mg', 'Boîte de 16 cp', 'N02BE',
                    '0', '24',
                    'Douleur, fièvre', 'Insuffisance hépatique', 'Nausées, éruption',
                    '15–25 °C', 'products/p1.jpg; products/p1b.jpg',
                    'Antalgique', 'Paracétamol 500mg',
                ]];

                $disk = Storage::disk('local');
                $dir  = 'tmp';
                $disk->makeDirectory($dir);
                $path = $disk->path("$dir/modele_produits_lisible.xlsx");

                SimpleExcelWriter::create($path)
                    ->addHeader($headers)
                    ->addRows($sample)
                    ->close();

                return response()->download($path)->deleteFileAfterSend(true);
            });
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
        if ($value === null || $value === '') {
            return null;
        }

        // tolère "1 234,56" ou "1,234.56"
        $s = Str::of((string) $value)->replace([' '], '');
        // si virgule comme décimal
        if ($s->contains(',') && ! $s->contains('.')) {
            $s = $s->replace(',', '.');
        }
        return is_numeric((string) $s) ? (float) $s : null;
    }

    private function normalizeCurrency($value): ?string
    {
        $cur = Str::upper(trim((string) $value));
        if ($cur === '') {
            return null;
        }

        // Liste courte (adapte si besoin)
        $allowed = ['USD', 'CDF', 'EUR', 'XAF', 'XOF'];
        return in_array($cur, $allowed, true) ? $cur : 'USD';
    }
}
