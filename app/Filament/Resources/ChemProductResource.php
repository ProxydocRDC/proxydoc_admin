<?php
namespace App\Filament\Resources;

use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ChemProduct;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use App\Imports\ChemProductsImport;
use App\Models\ChemPharmacyProduct;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ChemProductResource\Pages;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Tables\Filters\{SelectFilter,TernaryFilter,TrashedFilter,Filter};

class ChemProductResource extends Resource
{
    protected static ?string $model = ChemProduct::class;

                                                                           // Menu & libellés
    protected static ?string $navigationIcon   = 'heroicon-o-archive-box'; // icône “pilule”
    protected static ?string $navigationGroup  = 'Catalogue';
    protected static ?string $navigationLabel  = 'Produits';
    protected static ?string $modelLabel       = 'Produit';
    protected static ?string $pluralModelLabel = 'Produits';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Fiche produit")->schema([
                        Hidden::make('created_by')->default(Auth::id()),

                        Section::make('Identification')->schema([

                            TextInput::make('name')
                                ->label('Nom commercial')
                                ->required()
                                ->maxLength(500)
                                ->helperText('Nom commercial du produit (ex: Doliprane 500mg).')
                                ->columnSpan(4),

                            TextInput::make('generic_name')
                                ->label('Nom générique')
                                ->maxLength(500)
                                ->helperText('Nom générique du produit (ex: Paracétamol).')
                                ->columnSpan(4),

                            TextInput::make('brand_name')
                                ->label('Marque')
                                ->helperText('Nom de la marque (ex: Doliprane).')
                                ->maxLength(100)
                                ->columnSpan(4),
                        ])->columns(12),

                        Section::make('Classification')->schema([
                            Select::make('manufacturer_id')
                                ->label('Fabricant')
                                ->helperText('Sélectionnez le fabricant ou producteur du médicament.')
                                ->relationship('manufacturer', 'name')
                                ->searchable()->preload()
                                ->columnSpan(4),

                            Select::make('category_id')
                                ->label('Catégorie')
                                ->helperText('Catégorie de médicament (ex: Antibiotiques, Analgésiques).')
                                ->relationship('category', 'name')
                                ->searchable()->preload()
                                ->columnSpan(4),

                            Select::make('form_id')
                                ->label('Forme galénique')
                                ->relationship('form', 'name')
                                ->searchable()->preload()
                                ->helperText('Forme physique du médicament (ex: Comprimé, Sirop, Injection).')
                                ->columnSpan(4),
                        ])->columns(12),

                        Section::make('Spécifications')->schema([
                            TextInput::make('strength')
                                ->label('Dosage (strength)')
                                ->numeric()
                                ->helperText('Dosage ou concentration (ex: 500 pour 500mg).')
                                ->columnSpan(3),

                            TextInput::make('unit')
                                ->label('Unité (mg, ml, …)')
                                ->maxLength(10)
                                ->helperText('Unité de mesure (ex: mg, ml, L).')
                                ->columnSpan(3),

                            TextInput::make('packaging')
                                ->label('Conditionnement')
                                ->helperText('Type d’emballage (ex: Boîte de 20 comprimés).')
                                ->maxLength(50)
                                ->placeholder('Boîte de 20 cp')
                                ->columnSpan(6),

                            TextInput::make('atc_code')
                                ->label('Code ATC')
                                ->maxLength(10)
                                ->helperText('Code Anatomical Therapeutic Chemical pour identifier la molécule active.')
                                ->columnSpan(3),

                            TextInput::make('price_ref')
                                ->label('Prix de référence (USD)')
                                ->numeric()->minValue(0)->step('0.01')
                                ->helperText('Prix de référence du médicament, exprimé en USD.')
                                ->columnSpan(3),

                            Toggle::make('with_prescription')
                                ->label('Avec ordonnance ?')
                                ->inline(false)
                                ->helperText('Indique si le médicament nécessite une ordonnance pour être vendu.')
                                ->columnSpan(3),

                            TextInput::make('shelf_life_months')
                                ->label('Durée de conservation (mois)')
                                ->numeric()->minValue(0)
                                ->columnSpan(3)->helperText('Durée de conservation du produit en mois.'),
                        ])->columns(12),

                        Section::make('Textes médicaux & stockage')->schema([
                            Textarea::make('indications')->label('Indications')->maxLength(500)
                                ->columnSpan(6)->helperText('Utilisations médicales prévues pour ce produit.'),
                            Textarea::make('contraindications')->label('Contre‑indications')->maxLength(500)
                                ->columnSpan(6)->helperText('Situations dans lesquelles le médicament ne doit pas être utilisé.'),
                            Textarea::make('side_effects')->label('Effets secondaires')->maxLength(500)
                                ->columnSpan(6)->helperText('Liste des effets indésirables possibles.'),
                            TextInput::make('storage_conditions')->label('Conditions de stockage')->maxLength(100)
                                ->columnSpan(6)->helperText('Conditions optimales de conservation (ex: 15–25 °C).'),
                        ])->columns(12),

                        Section::make('Médias & descriptions')->schema([
                            // FileUpload::make('images')
                            //     ->label('Images')
                            //     ->disk('s3') // Filament uploade direct vers S3
                            //     ->directory('products')
                            //     ->disk('s3')            // Filament uploade direct vers S3
                            //     ->visibility('private') // ->visibility('public')                               // ou enlève pour bucket privé
                            //     ->multiple()
                            //     ->image()
                            //     ->getStateUsing(fn($record) => $record->mediaUrls('products'))
                            //     ->imageEditor() // optionnel
                            //     ->reorderable()
                            //     ->helperText('Tu peux déposer plusieurs images ; elles sont stockées sur S3.')
                            //     ->enableDownload()
                            //     ->enableOpen()

                            //     ->columnSpan(12),
                            FileUpload::make('images')
                                ->label('Images')
                                ->disk('s3')
                                ->directory('products')
                                ->visibility('private') // bucket privé => Filament fera des URLs signées
                                ->multiple()
                                ->image()
                                ->imageEditor()
                                ->reorderable()
                                ->preserveFilenames(false)
                                ->getUploadedFileNameForStorageUsing(
                                    fn(TemporaryUploadedFile $file) =>
                                    'products/' . Str::ulid() . '.' . $file->getClientOriginalExtension()
                                )
                            // ⚠️ important si tu as de vieilles URLs en base : convertir en clés pour la preview
                                ->formatStateUsing(function ($state) {
                                    $arr    = is_array($state) ? $state : (empty($state) ? [] : [$state]);
                                    $bucket = config('filesystems.disks.s3.bucket');

                                    $toKey = function ($v) use ($bucket) {
                                        if (! is_string($v) || $v === '') {
                                            return null;
                                        }

                                        if (! preg_match('#^https?://#i', $v)) {
                                            $key = ltrim($v, '/');
                                        } else {
                                            $p   = parse_url($v);
                                            $key = ltrim($p['path'] ?? '', '/');
                                            if ($bucket && str_starts_with($key, $bucket . '/')) {
                                                $key = substr($key, strlen($bucket) + 1);
                                            }
                                        }
                                        return $key ?: null;
                                    };

                                    return array_values(array_filter(array_map($toKey, $arr)));
                                })
                            // on écrit en base uniquement les CLÉS S3
                                ->dehydrateStateUsing(fn($state) => array_values($state ?? []))
                                ->helperText('Tu peux déposer plusieurs images ; elles sont stockées sur S3.')
                                ->columnSpan(12),
                            Textarea::make('description')
                                ->label('Description')
                                ->helperText('Description générale du produit.')
                                ->maxLength(1000)
                                ->columnSpan(12),

                            Textarea::make('composition')
                                ->label('Composition')
                                ->helperText('Ingrédients actifs et excipients contenus dans le produit.')
                                ->maxLength(1000)
                                ->columnSpan(12),
                        ]),
                    ])->columns(12),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
         ->modifyQueryUsing(function (Builder $q) {
            // preload for group headers/columns
            $q->with(['supplier','category','creator','updater']);

            // aliases we will actually use below
            // $q->addSelect([
            //     'affectations_count' => ChemPharmacyProduct::query()
            //         ->selectRaw('COUNT(*)')
            //         ->whereColumn('chem_pharmacy_products.product_id', 'chem_products.id'),
            //     'affectations_stock_sum' => ChemPharmacyProduct::query()
            //         ->selectRaw('COALESCE(SUM(stock_qty), 0)')
            //         ->whereColumn('chem_pharmacy_products.product_id', 'chem_products.id'),
            // ]);
        })
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->getStateUsing(function ($record, $column) {
                        $livewire = $column->getLivewire();
                        $perPage  = $livewire->getTableRecordsPerPage();
                        $page     = max(1, (int) $livewire->getTablePage());
                        $records  = $livewire->getTableRecords();

                        // position du record dans la page courante
                        $posInPage = $records->search(fn($r) => $r->getKey() === $record->getKey());
                        return ($page - 1) * $perPage + ($posInPage + 1);
                    })
                    ->alignCenter(),
                ImageColumn::make('images')
                    ->label('Images')
                    ->getStateUsing(fn($record) => $record->signedImageUrls(10)) // ← ARRAY d’URLs signées
                    ->circular()
                    ->stacked()
                    ->limit(2)
                    ->limitedRemainingText()
                    ->size(64)
                    ->square()
                    ->defaultImageUrl(asset('assets/images/default.jpg'))
                    ->url(fn($record) => $record->firstSignedImageUrl(60)) // clic = grande image
                    ->openUrlInNewTab(),
                // ViewColumn::make('images')
                //     ->label('Images')
                //     ->getStateUsing(fn($record) => $record->signedImageUrls(10)) // 3-4 miniatures
                //     ->view('tables.columns.product-images-thumbs'),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->wrap()
                    ->limit(40)
                    ->sortable(),

                TextColumn::make('generic_name')
                    ->label('Générique')
                    ->limit(30)
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('brand_name')
                    ->label('Marque')
                    ->toggleable()
                    ->searchable(),
 TextColumn::make('creator.name')
                ->label('Créé par')
                ->placeholder('—')
                ->badge()
                ->sortable(query: function (Builder $query, string $direction) {
                    // tri via sous-requête (pas besoin de JOIN)
                    $query->orderBy(
                        User::select('firstname, lastname, gender, phone')->whereColumn('users.id','chem_pharmacy_products.created_by'),
                        $direction
                    );
                })
                ->searchable(isIndividual: true, isGlobal: true),
                   TextColumn::make('updater.firstname')
                ->label('Modifié par')
                ->placeholder('—')
                ->badge()
                ->sortable(query: function (Builder $query, string $direction) {
                    $query->orderBy(
                        User::select('firstname, lastname, gender, phone')->whereColumn('users.id','chem_pharmacy_products.updated_by'),
                        $direction
                    );
                })
                ->searchable(isIndividual: true, isGlobal: true),
                TextColumn::make('manufacturer.name')
                    ->label('Fabricant')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('form.name')
                    ->label('Forme')
                    ->toggleable(),

                TextColumn::make('strength')
                    ->label('Dosage')
                    ->formatStateUsing(fn($state, $record) => $state ? rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.') . ' ' . ($record->unit ?? '') : '—')
                    ->toggleable(),

                // lecture : badge “Actif/Inactif”
                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($state) => (int) $state === 1 ? 'Actif' : 'Inactif')
                    ->colors([
                        'success' => fn($state) => (int) $state === 1,
                        'danger'  => fn($state)  => (int) $state === 0,
                    ])
                    ->sortable(),
                BadgeColumn::make('with_prescription')
                    ->label('Prescription')
                    ->formatStateUsing(fn($state) => (int) $state === 1 ? 'Obligatoir' : 'Optionelle')
                    ->colors([
                        'danger' => fn($state) => (int) $state === 1,
                        'success'  => fn($state)  => (int) $state === 0,
                    ])
                    ->sortable(),

                // édition inline : change le statut + toast
                SelectColumn::make('status')
                    ->label('Modifier')
                    ->options([1 => 'Actif', 0 => 'Inactif'])
                    ->afterStateUpdated(function ($record, $state) {
                        Notification::make()
                            ->title('Statut mis à jour')
                            ->body("Le produit « {$record->name} » est maintenant " . ((int) $state === 1 ? 'Actif' : 'Inactif') . '.')
                            ->success()
                            ->send();
                    }),

                TextColumn::make('price_ref')
                    ->label('Prix vente')
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ') . ' ' . ($record->currency ?? 'USD')
                    )
                    ->alignRight()
                    ->sortable(),
                // TextColumn::make('price_ref')
                //     ->label('Prix vente')
                //     ->money('USD') // devise fixe
                //     ->alignRight()
                //     ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                ->label('Statut')
                ->options([1 => 'Actif', 0 => 'Inactif']),

            // SelectFilter::make('manufacturer_id')
            //     ->label('Fournisseur')
            //     ->options(fn () => ChemSupplier::query()->orderBy('fullname')->pluck('fullname', 'id'))
            //     ->searchable()->preload()->indicator('Fournisseur'),

            // SelectFilter::make('category_id')
            //     ->label('Catégorie')
            //     ->options(fn () => ChemCategory::query()->orderBy('name')->pluck('name', 'id'))
            //     ->searchable()->preload()->indicator('Catégorie'),

            Filter::make('price_range')
                ->label('Prix réf.')
                ->form([
                    \Filament\Forms\Components\TextInput::make('min')->numeric()->label('Min'),
                    \Filament\Forms\Components\TextInput::make('max')->numeric()->label('Max'),
                ])
                ->query(function (Builder $q, array $data) {
                    return $q
                        ->when($data['min'] ?? null, fn ($qq, $min) => $qq->where('price_ref', '>=', $min))
                        ->when($data['max'] ?? null, fn ($qq, $max) => $qq->where('price_ref', '<=', $max));
                })
                ->indicateUsing(function (array $data) {
                    $chips = [];
                    if (!empty($data['min'])) $chips[] = 'Min: '.$data['min'];
                    if (!empty($data['max'])) $chips[] = 'Max: '.$data['max'];
                    return $chips;
                }),

            TernaryFilter::make('has_image')
                ->label('Avec image')
                ->trueLabel('Avec image')
                ->falseLabel('Sans image')
                ->queries(
                    true: fn (Builder $q) => $q->whereNotNull('images')->where('images','!=',''),
                    false: fn (Builder $q) => $q->whereNull('images')->orWhere('images',''),
                    blank: fn (Builder $q) => $q
                )
                ->indicator('Image'),

            TernaryFilter::make('is_assigned')
                ->label('Affecté à une pharmacie')
                ->trueLabel('Affecté')
                ->falseLabel('Non affecté')
                ->queries(
                    true: fn (Builder $q) => $q->whereHas('pharmacyProducts'),
                    false: fn (Builder $q) => $q->whereDoesntHave('pharmacyProducts'),
                    blank: fn (Builder $q) => $q
                )
                ->indicator('Affectation'),

            TrashedFilter::make(),

            ])
            ->actions([
                ActionGroup::make([
                    Action::make('clearImages')
                        ->label('Vider images')
                        ->icon('heroicon-m-photo')
                        ->color('warning')
                        ->visible(fn($record) => ! empty($record->images))
                        ->form([
                            \Filament\Forms\Components\Toggle::make('delete_s3')
                                ->label('Supprimer aussi les fichiers S3')
                                ->helperText('Sinon, seules les références en base seront vidées.')
                                ->default(false),
                        ])
                        ->requiresConfirmation()
                        ->action(function (array $data, $record) {
                            $keys    = is_array($record->images) ? $record->images : [];
                            $deleted = 0;

                            if (! empty($data['delete_s3']) && $keys) {
                                $disk = Storage::disk('s3');
                                foreach ($keys as $k) {
                                    // au cas où il resterait une URL complète :
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
                                        // on continue, on notifie juste à la fin
                                    }
                                }
                            }

                            // Vider la colonne en base (choix: [] plutôt que null)
                            $record->images = [];
                            $record->save();

                            Notification::make()
                                ->title('Images vidées')
                                ->body(($deleted ? "Fichiers S3 supprimés: {$deleted}. " : '') . 'La colonne "images" est maintenant vide.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\EditAction::make()->label('Modifier'),
                    Tables\Actions\DeleteAction::make()->label('Supprimer'),
                ]),
            ])->headerActions([
            Tables\Actions\Action::make('downloadTemplate')
                ->label('Télécharger le modèle')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('danger')
                ->url(fn() => route('products.template')) // ou .csv
                ->openUrlInNewTab()
                ->tooltip('Modèle avec en-têtes (et exemple) pour l’import des produits'),
            Tables\Actions\Action::make('importProducts')
                ->label('Importer')
                ->icon('heroicon-m-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('Fichier CSV/XLSX')
                        ->required()
                        ->disk('local')        // stockage temporaire local
                        ->directory('imports') // dossier
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain', // certains CSV
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->preserveFilenames()
                        ->downloadable() // permettre de re-télécharger
                        ->helperText('Utilise des entêtes : name, generic_name, brand_name, price_ref (obligatoires).
Optionnels : category_code, manufacturer_name, form_name, sku, barcode, strength, dosage, unit, stock, min_stock, price_sale, price_purchase, description, is_active.'),
                ])
                ->action(function (array $data) {
                    // 1) Récupérer le chemin du fichier
                    $path = $data['file'] ?? null;
                    if (! $path || ! Storage::disk('local')->exists($path)) {
                        Notification::make()->title('Fichier introuvable')->danger()->send();
                        return;
                    }

                    // 2) Lancer l’import
                    $import = new ChemProductsImport();

                    try {
                        Excel::import($import, Storage::disk('local')->path($path));
                        $failures   = $import->failures();
                        $errorCount = count($failures);
                        $created    = $import->created;
                        $updated    = $import->updated;

// --- construire un rapport détaillé & sauvegarder CSV
                        $rows = collect($failures)->map(function ($f) {
                            return [
                                'row'       => $f->row(),
                                'attribute' => $f->attribute(),
                                'message'   => implode('; ', $f->errors()),
                                'value'     => data_get($f->values(), $f->attribute()),
                            ];
                        });

                        $reportPath = null;
                        if ($errorCount > 0) {
                            $file = 'import_errors_' . now()->format('Ymd_His') . '.csv';
                            $dir  = storage_path('app/imports/reports');
                            if (! is_dir($dir)) {
                                mkdir($dir, 0775, true);
                            }

                            $fp = fopen($dir . DIRECTORY_SEPARATOR . $file, 'w');
                            fputcsv($fp, ['row', 'attribute', 'value', 'message']);
                            foreach ($rows as $r) {
                                fputcsv($fp, [$r['row'], $r['attribute'], (string) $r['value'], $r['message']]);
                            }
                            fclose($fp);
                            $reportPath = route('imports.report', ['file' => $file]);
                        }

// --- résumé court lisible dans la notif
                        $preview = $rows
                            ->sortBy('row')
                            ->take(5)
                            ->map(fn($r) => "Ligne {$r['row']} → {$r['attribute']}: {$r['message']} (valeur: \"{$r['value']}\")")
                            ->implode("\n");

                        $message = "Créés: {$created} • Mis à jour: {$updated} • Erreurs: {$errorCount}";
                        if ($errorCount > 0) {
                            $message .= "\n" . $preview;
                        }

                        $notif = \Filament\Notifications\Notification::make()
                            ->title('Import terminé')
                            ->body($message)
                            ->{$errorCount ? 'warning' : 'success'}()
                            ->persistent();

                        if ($reportPath) {
                            $notif->actions([
                                NotificationAction::make('Télécharger le rapport')->url($reportPath)->openUrlInNewTab(),
                            ]);
                        }

                        $notif->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Erreur pendant l’import')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        return;
                    }

                    // 3) Préparer un récap (succès / erreurs)
                    $created = $import->created;
                    $updated = $import->updated;

                    $failures   = $import->failures(); // liste des erreurs par ligne
                    $errorCount = count($failures);

                    $message = "Créés: {$created} • Mis à jour: {$updated}";
                    if ($errorCount > 0) {
                        $lines = collect($failures)
                            ->take(5) // on en montre 5 max dans la notif
                            ->map(fn($f) => "Ligne {$f->row()}: " . implode(', ', $f->errors()))
                            ->implode("\n");

                        $message .= "\nErreurs: {$errorCount}\n" . $lines;
                    }

                    // 4) Notification Filament
                    Notification::make()
                        ->title('Import terminé')
                        ->body($message)
                        ->{$errorCount > 0 ? 'warning' : 'success'}()
                        ->persistent() // reste visible
                        ->send();

                    // 5) (optionnel) supprimer le fichier
                    // Storage::disk('local')->delete($path);
                }),
        ])
            ->bulkActions([
                // … tes autres bulk actions
                BulkAction::make('clearImagesBulk')
                    ->label('Vider images (en masse)')
                    ->icon('heroicon-m-photo')
                    ->color('warning')
                    ->deselectRecordsAfterCompletion()
                    ->form([
                        \Filament\Forms\Components\Toggle::make('delete_s3')
                            ->label('Supprimer aussi les fichiers S3')
                            ->default(false),
                    ])
                    ->action(function (array $data, $records) {
                        $disk              = Storage::disk('s3');
                        $totalRecords      = 0;
                        $totalFilesDeleted = 0;

                        foreach ($records as $record) {
                            $totalRecords++;
                            $keys = is_array($record->images) ? $record->images : [];

                            if (! empty($data['delete_s3']) && $keys) {
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
                                            $totalFilesDeleted++;
                                        }
                                    } catch (\Throwable $e) {
                                        // on ignore, on continue sur les autres
                                    }
                                }
                            }

                            $record->images = [];
                            $record->save();
                        }

                        Notification::make()
                            ->title('Images vidées')
                            ->body("Produits traités: {$totalRecords}. Fichiers S3 supprimés: {$totalFilesDeleted}.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make()->label('Supprimer la sélection'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemProducts::route('/'),
            'create' => Pages\CreateChemProduct::route('/create'),
            'edit'   => Pages\EditChemProduct::route('/{record}/edit'),
        ];
    }
     public static function getNavigationBadge(): ?string
    {

        $base = ChemProduct::query();

        return (string) $base->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning'; // ou 'success', 'warning', etc.
    }
}
