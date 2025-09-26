<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ChemProduct;
use App\Models\ChemPharmacy;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use App\Models\ChemPharmacyProduct;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\ChemPharmacyProductsExport;
use App\Imports\ChemPharmacyProductsImport;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Count;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Grouping\Group as groupingGroup;
use App\Filament\Resources\ChemPharmacyProductResource\Pages;
use Filament\Notifications\Actions\Action as NotificationAction;

class ChemPharmacyProductResource extends Resource
{

    // use RestrictToSupplier;

    protected static ?string $model = ChemPharmacyProduct::class;

    protected static ?string $navigationIcon   = 'heroicon-o-shopping-bag'; // Icône adaptée pour un produit
    protected static ?string $navigationLabel  = 'Produits Pharmacies';
    protected static ?string $modelLabel       = 'Produit de Pharmacie';
    protected static ?string $pluralModelLabel = 'Produits de Pharmacies';
    protected static ?string $navigationGroup  = 'Gestion Pharmacie';
    protected static ?int $navigationSort      = 6;

    protected static function supplierOwnerPath(): string
    {
        return 'pharmacy.supplier_id'; // via relation
    }
    public static function getEloquentQuery(): Builder
    {
        // Requête la plus simple: pas de scope, pas de filtre
        // return ChemPharmacy::query()->withoutGlobalScopes([SoftDeletingScope::class]);

        $q = ChemPharmacyProduct::query()->withoutGlobalScopes([SoftDeletingScope::class]);
        $u = Auth::user();

        if ($u?->hasRole('fournisseur')) {
            $q->where('supplier_id', optional($u->supplier)->id);
        }

        return $q;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire Produit Pharmacie")->schema([
                        Hidden::make('created_by')->default(Auth::id()),

                        Select::make('pharmacy_id')
                            ->label('Pharmacie')
                            ->options(function () {
                                $u    = Auth::user();
                                $base = \App\Models\ChemPharmacy::query();

                                if ($u?->hasRole('super_admin')) {
                                    return $base->orderBy('name')->pluck('name', 'id');
                                }

                                $sid = $u?->supplier?->id ?? 0;
                                return $base->where('supplier_id', $sid)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        // ❌ SUPPRIMER ce bloc : relation inexistante et conceptuellement faux ici
                        // Select::make('pharmacies')->multiple()->relationship('pharmacies', ...)
                        Select::make('product_id')
                            ->label('Produit')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        // Select::make('product_id')
                        //     ->label('Produit')
                        //     ->relationship('product', 'name')
                        //     ->searchable()
                        //     ->preload()
                        //     ->required()
                        //     ->helperText('Choisissez le produit pharmaceutique dans la liste.'),

                        TextInput::make('lot_ref')->label('Référence du lot')->maxLength(50),
                        TextInput::make('origin_country')->label('Code pays d\'origine')->maxLength(3)->default('COD'),

                        DatePicker::make('expiry_date')->label('Date d\'expiration'),

                        TextInput::make('cost_price')->label('Prix d\'achat')->numeric()->prefix('$'),
                        TextInput::make('sale_price')->label('Prix de vente')->numeric()->prefix('$')->required(),

                        Select::make('currency')->label('Devise')->options([
                            'USD' => 'USD - Dollar américain',
                            'CDF' => 'CDF - Franc congolais',
                        ])->required(),

                        TextInput::make('stock_qty')->label('Quantité en stock')->numeric()->default(0)->required(),
                        TextInput::make('reorder_level')->label('Niveau d\'alerte stock')->numeric(),

                        FileUpload::make('image')
                            ->label('Image du produit')
                            ->image()
                            ->directory('pharmacy_products')
                            ->imageEditor()
                            ->disk('s3')            // Filament uploade direct vers S3
                            ->visibility('private') // retire si privé
                            ->enableDownload()      // (au lieu de ->downloadable())
                            ->enableOpen()
                            ->helperText('Téléchargez une image du produit (max 2 Mo).'),

                        Textarea::make('description')->label('Description')->maxLength(1000),
                    ])->columns(2),

                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        // charge le nom de la pharmacie
        //     ->modifyQueryUsing(fn($query) => $query->with(['pharmacy','product']))
        //     // ✅ regroupe par pharmacie
        //     ->groups([
        //         groupingGroup::make('pharmacy.name')
        //             ->label('Pharmacie')
        //             ->collapsible(), // groupes pliables
        //     ])
        //     ->defaultGroup('pharmacy.name') // ouvre déjà groupé
        //       // 🔎 Recherche globale & par colonne
        // ->persistSearchInSession()           // mémorise la recherche globale
        // ->persistColumnSearchesInSession()   // mémorise les recherches par colonne
        //     ->columns([
        //         TextColumn::make('pharmacy.name')->label('Pharmacie')
        //          ->searchable(isIndividual: true, isGlobal: true) // colonne & global
        //         ->sortable(), // apparait aussi dans l’entête de groupe
        //         TextColumn::make('product.name')->label('Produit')
        //         ->searchable(isIndividual: true, isGlobal: true)
        //         ->sortable(),
        //         TextColumn::make('stock_qty')->label('Stock')
        //         // Totaux par groupe (en bas du groupe)
        //             ->summarize([
        //                 Sum::make()->label('Stock total'),
        //             ]),
        //             TextColumn::make('lot_ref')
        //         ->label('Lot')
        //         ->searchable(isIndividual: true, isGlobal: true),
        //           TextColumn::make('sale_price')
        //         ->label('Prix vente')
        //         ->money('USD', true) // adapte devise si besoin
        //         ->sortable(),
        //         TextColumn::make('id')
        //             ->label('Lignes')
        //             ->summarize([
        //                 Count::make()->label('Produits (lignes)'),
        //             ]),
        //         // … autres colonnes …
        //     ])
            // ->columns([
            //     // ImageColumn::make('image')
            //     //     ->label('Image')
            //     //     ->square()
            //     //     ->size(50)
            //     //     ->getStateUsing(fn($record) => $record->mediaUrl('image')) // URL finale
            //     //     ->size(64)
            //     //     ->square()
            //     //     ->defaultImageUrl(asset('assets/images/default.jpg')) // 👈 évite l’icône cassée
            //     //     ->openUrlInNewTab()
            //     //     ->url(fn($record) => $record->mediaUrl('image', ttl: 5)), // clic = grande image,
            //     // ImageColumn::make('image')
            //     //     ->label('Image')
            //     //     ->square()
            //     //     ->size(64)
            //     // // miniature signée (image propre si présente, sinon image du produit)
            //     //     ->getStateUsing(fn($record) => $record->displayImageUrl(10))
            //     //     ->defaultImageUrl(asset('assets/images/default.jpg'))
            //     //     ->openUrlInNewTab()
            //     // // clic : version avec TTL plus long
            //     //     ->url(fn($record) => $record->displayImageUrl(60)),

            //     // TextColumn::make('pharmacy.name')
            //     //     ->label('Pharmacie')
            //     //     ->sortable()
            //     //     ->searchable(),

            //     // TextColumn::make('product.name')
            //     //     ->label('Produit')
            //     //     ->sortable()
            //     //     ->searchable(),

            //     // TextColumn::make('lot_ref')
            //     //     ->label('Lot Ref'),

            //     // TextColumn::make('origin_country')
            //     //     ->label('Pays Origine'),

            //     // TextColumn::make('expiry_date')
            //     //     ->label('Expiration')
            //     //     ->date('d/m/Y'),

            //     // TextColumn::make('sale_price')
            //     //     ->label('Prix Vente')
            //     //     ->money(fn($record) => $record->currency),

            //     // TextColumn::make('stock_qty')
            //     //     ->label('Stock'),

            //     // TextColumn::make('created_by')
            //     //     ->label('Créé par')
            //     //     ->formatStateUsing(fn($state) => \App\Models\User::find($state)?->name ?? '—'),
            // ])
            ->defaultSort('id', 'desc')
          // On précharge la pharmacie + ses agrégats pour construire le label du groupe
        ->modifyQueryUsing(fn ($query) => $query->with(['pharmacy', 'product']))
        ->groups([
            groupingGroup::make('pharmacy_id')->label('Pharmacie')->collapsible(),
        ])
        ->defaultGroup('pharmacy_id')          // ✅ OK
        ->defaultSort('pharmacy_id')           // ✅ pas de colonne relationnelle ici

        // ✅ Groupement par pharmacie avec entête custom
        ->groups([
            groupingGroup::make('pharmacy_id')
                ->label('Pharmacie')
                ->getTitleFromRecordUsing(function ($record) {
                    $p      = $record->pharmacy;
                    $name   = $p?->name ?? '—';
                    $count  = (int) ($p?->pharmacy_products_count ?? 0);
                    $stock  = (int) ($p?->pharmacy_products_sum_stock_qty ?? 0);

                    // Formatage gentil (espaces comme séparateurs de milliers)
                    $stockFmt = number_format($stock, 0, ',', ' ');

                    return "Pharmacie : {$name} ({$count} produits, Stock total : {$stockFmt})";
                })
                ->collapsible(),
        ])
        ->defaultGroup('pharmacy_id')

        // Colonnes (exemple)
        ->columns([
            TextColumn::make('product.name')->label('Produit')
                ->searchable(isIndividual: true, isGlobal: true)
                ->sortable(),

            TextColumn::make('stock_qty')->label('Stock')
                ->sortable()
                ->summarize([
                    Sum::make()->label('Stock total'),
                ]),
        TextColumn::make('lot_ref')
                ->label('Lot')
                ->searchable(isIndividual: true, isGlobal: true),

            TextColumn::make('sale_price')
                ->label('Prix vente')
                ->money('USD', true) // adapte devise si besoin
                ->sortable(),
            // Un compteur de lignes au pied de groupe/table (optionnel)
            TextColumn::make('id')
                ->label('Lignes')
                ->summarize([
                    Count::make()->label('Produits (lignes)'),
                ]),
                TextColumn::make('description')
                ->label('Description')
                ->toggleable()
                ->limit(50)
                ->searchable(isIndividual: true, isGlobal: true),
        ])
            ->filters([
                 // Filtre par pharmacie
            SelectFilter::make('pharmacy_id')
                ->label('Pharmacie')
                ->options(function () {
                    $u    = Auth::user();
                    $base = ChemPharmacy::query();

                    if (! $u?->hasRole('super_admin')) {
                        $sid = $u?->supplier?->id ?? 0;
                        $base->where('supplier_id', $sid);
                    }

                    return $base->orderBy('name')->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->indicator('Pharmacie'),

            // Filtre par produit
            SelectFilter::make('product_id')
                ->label('Produit')
                ->options(fn () => ChemProduct::query()
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->indicator('Produit'),
            ])
            ->headerActions([
                // Modèle
                Action::make('template')
                    ->label('Télécharger le modèle')
                    ->icon('heroicon-m-document-arrow-down')
                    ->url(fn() => route('pharmacy_products.template.csv'))
                    ->openUrlInNewTab(),

                // Import
                // Import
                Action::make('import')
                    ->label('Importer')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->form([
                        FileUpload::make('file')
                            ->label('Fichier CSV/XLSX')
                            ->required()
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'text/csv', 'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->preserveFilenames()
                            ->downloadable()
                            ->helperText(
                                'Pharmacie: pharmacy_id ou pharmacy_name.
Produit: product_id ou product_sku ou product_name.
Manufacturer (optionnel): manufacturer_id ou manufacturer_name.
Champs: status, lot_ref, origin_country(3), expiry_date(YYYY-MM-DD), cost_price, sale_price*,
currency*(USD/CDF), stock_qty, reorder_level, image(clé S3), description.'
                            ),
                    ])
                    ->action(function (array $data, Action $action): void {
                        $path = $data['file'] ?? null;

                        if (! $path || ! Storage::disk('local')->exists($path)) {
                            Notification::make()->title('Fichier introuvable')->danger()->send();
                            return;
                        }

                        $import = new ChemPharmacyProductsImport(Auth::id());

                        try {
                            Excel::import($import, Storage::disk('local')->path($path));
                        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                            // ok : les échecs ligne à ligne sont récupérés ci-dessous
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erreur pendant l’import')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            return;
                        }

                        // ====== Récap + rapport d’échecs ======
                        $failures   = $import->failures();
                        $errorCount = count($failures);
                        $created    = $import->created;
                        $updated    = $import->updated;

                        $rows = collect($failures)->map(function ($f) {
                            return [
                                'row'       => $f->row(),
                                'attribute' => $f->attribute(),
                                'value'     => (string) data_get($f->values(), $f->attribute()),
                                'message'   => implode('; ', $f->errors()),
                            ];
                        });

                        $reportUrl = null;
                        if ($errorCount > 0) {
                            $dir = storage_path('app/imports/reports');
                            if (! is_dir($dir)) {
                                mkdir($dir, 0775, true);
                            }

                            $file = 'import_pharmacy_products_errors_' . now()->format('Ymd_His') . '.csv';
                            $fp   = fopen($dir . DIRECTORY_SEPARATOR . $file, 'w');

                            fputcsv($fp, ['row', 'attribute', 'value', 'message']);
                            foreach ($rows as $r) {
                                fputcsv($fp, [$r['row'], $r['attribute'], $r['value'], $r['message']]);
                            }
                            fclose($fp);

                            $reportUrl = route('imports.report', ['file' => $file]);
                        }

                        $preview = $rows
                            ->sortBy('row')
                            ->take(5)
                            ->map(fn($r) => "Ligne {$r['row']} → {$r['attribute']}: {$r['message']} (valeur: \"{$r['value']}\")")
                            ->implode("\n");

                        $message = "Créés: {$created} • Mis à jour: {$updated} • Erreurs: {$errorCount}";
                        if ($errorCount && $preview) {
                            $message .= "\n" . $preview;
                        }

                        $notif = Notification::make()
                            ->title('Import terminé')
                            ->body($message)
                            ->{$errorCount ? 'warning' : 'success'}()
                            ->persistent();

                        if ($reportUrl) {
                            $notif->actions([
                                NotificationAction::make('Télécharger le rapport')
                                    ->url($reportUrl)
                                    ->openUrlInNewTab(),
                            ]);
                        }

                        $notif->send();

                        // ✅ Rafraîchir la table SANS redirection (évite /livewire/update en GET)
                        $action->getLivewire()->js('$wire.$refresh()');
                        // (Alternative : $action->getLivewire()->dispatch("refresh"); selon ta version)
                    }),

                // Export
                Action::make('export')
                    ->label('Exporter')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(fn() => \Maatwebsite\Excel\Facades\Excel::download(
                        new ChemPharmacyProductsExport(),
                        'pharmacy_products.xlsx'
                    )),
                // 👉 Action “Affecter plusieurs produits”
                Action::make('bulkAssignToPharmacy')
                    ->label('Affecter plusieurs produits')
                    ->icon('heroicon-m-plus-circle')
                    ->modalHeading('Affecter plusieurs produits à une pharmacie')
                    ->modalSubmitActionLabel('Affecter maintenant')
                    ->color('primary')
                    ->form(self::bulkAssignFormSchema())
                    ->action(function (array $data): void {
                        // Sécurité minimale
                        $user = Auth::user();

                        // Rassemblement des IDs produits choisis
                        $pharmacyId = (int) $data['pharmacy_id'];
                        $productIds = array_values(array_unique($data['product_ids'] ?? []));
                        if (empty($productIds)) {
                            // Filament notifiera ce message en toast
                            Notification::make()
                                ->title('Veuillez sélectionner au moins un produit.')
                                ->warning()
                                ->send();
                            return;
                        }
                        // 1) Récupère les produits déjà présents dans cette pharmacie
                        $existingIds = \App\Models\ChemPharmacyProduct::query()
                            ->where('pharmacy_id', $pharmacyId)
                            ->whereIn('product_id', $productIds)
                            ->pluck('product_id')
                            ->all();
// 2) Calcule ceux à créer & à ignorer
                        $toCreateIds = array_values(array_diff($productIds, $existingIds));
                        $created     = count($toCreateIds);
                        $skipped     = count($productIds) - $created;

                        // 3) Prépare le payload commun
                        $base = [
                            'pharmacy_id'    => $pharmacyId,
                            'lot_ref'        => $data['lot_ref'] ?? null,
                            'origin_country' => $data['origin_country'] ?? 'COD',
                            'expiry_date'    => $data['expiry_date'] ?? null,
                            'cost_price'     => $data['cost_price'] ?? null,
                            'sale_price'     => $data['sale_price'],
                            'currency'       => $data['currency'],
                            'stock_qty'      => $data['stock_qty'] ?? 0,
                            'reorder_level'  => $data['reorder_level'] ?? null,
                            'image'          => $data['image'] ?? null,
                            'description'    => $data['description'] ?? null,
                            'created_by'     => $user?->id,
                            'status'         => -1,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ];
                        // 4) Insert groupé (si nécessaire)
                        if ($created > 0) {
                            // $rows = array_map(fn($pid) => ['product_id' => $pid] + $base, $toCreateIds);

                            // DB::transaction(function () use ($rows) {
                            //     \App\Models\ChemPharmacyProduct::query()->insert($rows);
                            // });
                            DB::transaction(function () use ($productIds, $base, &$created) {
                                foreach ($productIds as $pid) {
                                    $product = \App\Models\ChemProduct::find($pid);

                                    // Prix de vente : si non saisi → on prend price_ref du produit
                                    $salePrice = $base['sale_price'] ?? $product->price_ref;

                                    \App\Models\ChemPharmacyProduct::create([
                                        'product_id' => $pid,
                                        'sale_price' => $salePrice,
                                    ] + $base);

                                    $created++;
                                }
                            });
                        }

                        Notification::make()
                            ->title('Affectation terminée')
                            ->body("Créés : {$created} • Ignorés (déjà présents) : {$skipped}")
                            ->success()
                            ->send();
                        // On applique les champs “communs” à toutes les lignes créées
                        $payloadCommon = [
                            'pharmacy_id'    => $data['pharmacy_id'],
                            'lot_ref'        => $data['lot_ref'] ?? null,
                            'origin_country' => $data['origin_country'] ?? 'COD',
                            'expiry_date'    => $data['expiry_date'] ?? null,
                            'cost_price'     => $data['cost_price'] ?? null,
                            'sale_price'     => $data['sale_price'],
                            'currency'       => $data['currency'],
                            'stock_qty'      => $data['stock_qty'] ?? 0,
                            'reorder_level'  => $data['reorder_level'] ?? null,
                            'image'          => $data['image'] ?? null, // chemin retourné par FileUpload
                            'description'    => $data['description'] ?? null,
                            'created_by'     => $user?->id,
                        ];

                    })
                // Optionnel : autorisation via policy
                    ->authorize(fn() => Auth::user()?->can('create', ChemPharmacyProduct::class) ?? false),

            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Supprimer sélection'),
            ])->filtersFormColumns(2)
        ->persistFiltersInSession();   // mémorise les filtres choisis

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
            'index'  => Pages\ListChemPharmacyProducts::route('/'),
            'create' => Pages\CreateChemPharmacyProduct::route('/create'),
            'edit'   => Pages\EditChemPharmacyProduct::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'fournisseur']) ?? false;
    }
    /**
     * Schéma du formulaire du popup d’affectation multiple.
     */
    protected static function bulkAssignFormSchema(): array
    {
        return [
            Group::make([
                Section::make('Cible')
                    ->schema([
                        Hidden::make('created_by')->default(fn() => Auth::id()),

                        // Sélection de la pharmacie (filtrée par fournisseur si pas super_admin)
                        Select::make('pharmacy_id')
                            ->label('Pharmacie')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $u    = Auth::user();
                                $base = ChemPharmacy::query();

                                if ($u?->hasRole('super_admin')) {
                                    return $base->orderBy('name')->pluck('name', 'id');
                                }

                                $sid = $u?->supplier?->id ?? 0;
                                return $base->where('supplier_id', $sid)->orderBy('name')->pluck('name', 'id');
                            })
                            ->helperText("Choisissez la pharmacie à laquelle affecter les produits."),
                    ])
                    ->columns(1),

                Section::make('Produits à affecter')
                    ->schema([
                        // Sélection multiple des produits
                        Select::make('product_ids')
                            ->label('Produits')
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function (callable $get) {
                                $pharmacyId = $get('pharmacy_id');
                                if (! $pharmacyId) {
                                    return [];
                                }

                                // IDs déjà affectés à cette pharmacie
                                $alreadyAssigned = \App\Models\ChemPharmacyProduct::query()
                                    ->where('pharmacy_id', $pharmacyId)
                                    ->pluck('product_id');

                                // On propose uniquement les produits pas encore affectés
                                return \App\Models\ChemProduct::query()
                                    ->whereNotIn('id', $alreadyAssigned)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->helperText('Seuls les produits non encore affectés à cette pharmacie apparaissent.'),
                    ])
                    ->columns(1),

                Section::make('Informations générales (appliquées à tous)')
                    ->schema([
                        TextInput::make('lot_ref')->label('Référence du lot')->maxLength(50),
                        TextInput::make('origin_country')->label('Code pays d\'origine')->maxLength(3)->default('COD'),
                        DatePicker::make('expiry_date')->label('Date d\'expiration'),

                        TextInput::make('cost_price')->label('Prix d\'achat')->numeric()->prefix('$'),
                        TextInput::make('sale_price')->label('Prix de vente')->numeric()->prefix('$'),

                        Select::make('currency')->label('Devise')->options([
                            'USD' => 'USD - Dollar américain',
                            'CDF' => 'CDF - Franc congolais',
                        ])->required(),

                        TextInput::make('stock_qty')->label('Quantité en stock')->numeric()->default(0)->required(),
                        TextInput::make('reorder_level')->label('Niveau d\'alerte stock')->numeric(),

                        FileUpload::make('image')
                            ->label('Image (optionnelle, appliquée à tous)')
                            ->image()
                            ->directory('pharmacy_products')
                            ->imageEditor()
                            ->disk('s3')
                            ->visibility('private')
                            ->enableDownload()
                            ->enableOpen()
                            ->helperText('Image appliquée à toutes les insertions (max 2 Mo).'),

                        Textarea::make('description')->label('Description')->maxLength(1000),
                    ])
                    ->columns(2),
            ])->columnSpanFull(),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        // total global de lignes “produit en pharmacie”
        $u    = Auth::user();
        $base = ChemPharmacy::query();

        if (! $u?->hasRole('super_admin')) {
            $sid = $u?->supplier?->id ?? 0;
            $base->where('supplier_id', $sid);
        }

        return (string) $base->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary'; // ou 'success', 'warning', etc.
    }
}
