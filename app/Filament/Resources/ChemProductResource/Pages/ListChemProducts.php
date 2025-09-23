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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListChemProducts extends ListRecords
{
    protected static string $resource = ChemProductResource::class;

    /** En-têtes attendues dans le fichier importé */
    public const REQUIRED_HEADERS = [
        'code', 'label', 'description', 'status', 'category_code', 'manufacturer_code', 'price', 'currency',
    ];
   public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->badge( (string) ChemProduct::query()->count() ),

            'without_images' => Tab::make('Sans image')
                ->badge( (string) ChemProduct::withoutImages()->count() )
                ->modifyQueryUsing(fn (Builder $q) => $q->withoutImages()),
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un produit')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),
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
        ];
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
