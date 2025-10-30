<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\ChemPharmacy;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ChemPharmaciesExport;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Filament\RestrictToSupplier;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ChemPharmacyResource\Pages;
use Filament\Notifications\Actions\Action as NotificationAction;

class ChemPharmacyResource extends Resource
{
    // use RestrictToSupplier;
    protected static ?string $model = ChemPharmacy::class;

    protected static ?string $navigationIcon   = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?string $navigationLabel  = 'Pharmacies';
    protected static ?string $modelLabel       = 'Pharmacie';
    protected static ?string $pluralModelLabel = 'Pharmacies';

    protected static function supplierOwnerPath(): string
    {
        return 'supplier_id'; // direct
    }
    public static function getEloquentQuery(): Builder
    {
        // Requête la plus simple: pas de scope, pas de filtre
        // return ChemPharmacy::query()->withoutGlobalScopes([SoftDeletingScope::class]);

        $record = ChemPharmacy::query()->withoutGlobalScopes([SoftDeletingScope::class]);
        $u = Auth::user();

        if ($u?->hasRole('fournisseur')) {
            $record->where('supplier_id', optional($u->supplier)->id);
        }

        return $record;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire pour ajouter une pharmacie")
                        ->schema([
                            // demandé : created_by rempli, visible, non modifiable, envoyé
                            Hidden::make('created_by')
                                ->label('Créé par (ID utilisateur)')
                                ->default(Auth::id())
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->columnSpan(12),

                            Section::make('Informations générales')
                                ->schema([

                                    TextInput::make('name')
                                        ->label('Nom de la pharmacie')
                                        ->required()
                                        ->maxLength(256)
                                        ->columnSpan(6),

                                    TextInput::make('phone')
                                        ->label('Téléphone')
                                        ->maxLength(20)
                                        ->tel()
                                        ->columnSpan(6),

                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->maxLength(256)
                                        ->columnSpan(6),

                                    TextInput::make('address')
                                        ->label('Adresse')
                                        ->maxLength(500)
                                        ->columnSpan(6),

                                    Textarea::make('description')
                                        ->label('Description')
                                        ->maxLength(1000)
                                        ->rows(3)
                                        ->columnSpan(12),
                                ])
                                ->columns(12),

                            Section::make('Liens & zone')
                                ->schema([
                                    Select::make('user_id')
                                        ->label("Utilisateur (fournisseur)")
                                        ->options(fn() =>
                                            \App\Models\User::query()
                                                ->when(Auth::user()?->hasRole('fournisseur'),
                                                    fn($record) => $record->whereKey(Auth::id()))
                                                ->pluck('firstname', 'id')
                                        )
                                        ->default(fn() => Auth::id())
                                        ->disabled(fn() => Auth::user()?->hasRole('fournisseur'))
                                        ->searchable()
                                        ->dehydrated(true)
                                    // 12 colonnes sur mobile, 6 à partir de md
                                        ->columnSpan(['default' => 12, 'md' => 6])
                                        ->preload()
                                        ->required(),

                                    Select::make('supplier_id')
                                        ->label("Fournisseur")
                                        ->options(fn() =>
                                            \App\Models\ChemSupplier::query()
                                                ->when(Auth::user()?->hasRole('fournisseur'),
                                                    fn($record) => $record->whereKey(Auth::user()->supplier?->id ?? 0))
                                                ->pluck('company_name', 'id')
                                        )
                                        ->default(fn() => Auth::user()?->supplier?->id)
                                        ->disabled(fn() => Auth::user()?->hasRole('fournisseur'))
                                        ->searchable()
                                        ->dehydrated(true)
                                        ->preload()
                                        ->required()
                                    // 12 colonnes sur mobile, 6 à partir de md
                                        ->columnSpan(['default' => 12, 'md' => 6]),

                                    Select::make('zone_id')
                                        ->label("Zone")
                                        ->relationship('zone', 'name') // adapte
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(4),
                                ])
                                ->columns(12),

                            Section::make('Logo')
                                ->schema([
                                    FileUpload::make('logo')
                                        ->label('Logo')
                                        ->image()
                                        ->imagePreviewHeight('150')
                                        ->maxSize(2048)
                                        ->directory('pharmacies')
                                        ->imageEditor()
                                        ->disk('s3')            // Filament uploade direct vers S3
                                        ->visibility('private') // retire si privé
                                        ->columnSpan(12),

                                ]),
                        ])
                        ->columns(12),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
        ->modifyQueryUsing(fn ($record) => $record->withCount('pharmacyProducts'))
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->height(40)
                    ->getStateUsing(fn($record) => $record->mediaUrl('logo')) // URL finale
                    ->size(64)
                    ->square()
                    ->defaultImageUrl(asset('assets/images/default.jpg')) // 👈 évite l’icône cassée
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->mediaUrl('logo', ttl: 5)), // clic = grande image,,

                TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),
TextColumn::make('pharmacy_products_count')   // ✅ snake_case
    ->label('Produits')
    ->counts('pharmacyProducts')              // génère le withCount
    ->badge()
    ->sortable(),

                TextColumn::make('phone')->label('Téléphone'),
                TextColumn::make('email')->label('Email'),

                TextColumn::make('address')
                    ->label('Adresse')
                    ->limit(30),

                TextColumn::make('user.fullname') // adapte le champ d’affichage
                    ->label('Propriétaireb')
                    ->toggleable(),
TextColumn::make('pharmacy_products_count')
    ->label('Produits')
    ->counts('pharmacyProducts')   // ok car la relation est sur ChemPharmacy
    ->badge(),
                TextColumn::make('supplier.company_name')
                    ->label('Entreprise')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('MAJ le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([1 => 'Actif', 0 => 'Inactif']),
            ])
            ->headerActions([
                Action::make('reset')
                    ->label('Réinitialiser la vue')->icon('heroicon-m-arrow-path')
                    ->action(fn() => redirect(request()->url())),

                // Télécharger le modèle CSV
                Action::make('templateCsv')
                    ->label('Télécharger le modèle')
                    ->icon('heroicon-m-document-arrow-down')
                    ->url(fn() => route('pharmacies.template.csv'))
                    ->openUrlInNewTab(),

                // Importer CSV/XLSX
                Action::make('importPharmacies')
                    ->label('Importer')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->form([
                        FileUpload::make('file')
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
                            ->helperText('En-têtes supportés : name*, zone_id*, status, phone, email, address, description, logo (clé S3 ou URL),
supplier_id (id) OU supplier_name (nom), user_id (id) OU user_email (email) OU user_name (nom), rating, nb_review. (* requis)'),
                    ])
                    ->action(function (array $data) {
                        $path = $data['file'] ?? null;
                        if (! $path || ! Storage::disk('local')->exists($path)) {
                            Notification::make()->title('Fichier introuvable')->danger()->send();
                            return;
                        }

                        $import = new \App\Imports\ChemPharmaciesImport(Auth::id());

                        try {
                            Excel::import($import, Storage::disk('local')->path($path));
                        } catch (\Maatwebsite\Excel\Validators\ValidationException $ve) {
                            // ok: les échecs seront disponibles via $import->failures()
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erreur pendant l’import')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            return;
                        }

                        $failures   = $import->failures();
                        $errorCount = count($failures);
                        $created    = $import->created;
                        $updated    = $import->updated;

                        // ---------- Génération du rapport CSV ----------
                        $rows = collect($failures)->map(function ($f) {
                            return [
                                'row'       => $f->row(),                                        // N° de ligne (dans le fichier)
                                'attribute' => $f->attribute(),                                  // colonne fautive
                                'value'     => (string) data_get($f->values(), $f->attribute()), // valeur source
                                'message'   => implode('; ', $f->errors()),                      // message(s)
                            ];
                        });

                        $reportUrl = null;
                        if ($errorCount > 0) {
                            $dir = storage_path('app/imports/reports');
                            if (! is_dir($dir)) {
                                mkdir($dir, 0775, true);
                            }

                            $file = 'import_pharmacies_errors_' . now()->format('Ymd_His') . '.csv';
                            $fp   = fopen($dir . DIRECTORY_SEPARATOR . $file, 'w');

                            // entêtes CSV
                            fputcsv($fp, ['row', 'attribute', 'value', 'message']);

                            // lignes
                            foreach ($rows as $r) {
                                fputcsv($fp, [$r['row'], $r['attribute'], $r['value'], $r['message']]);
                            }
                            fclose($fp);

                            $reportUrl = route('imports.report', ['file' => $file]);
                        }

                        // ---------- Aperçu lisible dans la notif ----------
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
                        // $livewire->dispatch('refresh');
                        if ($reportUrl) {
                            $notif->actions([
                                NotificationAction::make('Télécharger le rapport')->url($reportUrl)->openUrlInNewTab(),
                            ]);
                        }

                        $notif->send();
                    }),

                // Exporter Excel
                Action::make('exportPharmacies')
                    ->label('Exporter')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(function () {
                        return Excel::download(new ChemPharmaciesExport(), 'pharmacies.xlsx');
                    }),
            ])
            ->actions([
                ActionGroup::make([
                Tables\Actions\Action::make('orders')
                    ->label('Commandes')
                    ->icon('heroicon-o-receipt-percent')
                    ->url(fn($record) =>
                        static::getUrl('edit', ['record' => $record]) . '#relationManager=orders'
                    ),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),

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
                            $keys    = is_array($record->logo) ? $record->logo : null;
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
                            $record->logo = null;
                            $record->save();

                            Notification::make()
                                ->title('Images vidées')
                                ->body(($deleted ? "Fichiers S3 supprimés: {$deleted}. " : '') . 'La colonne "images" est maintenant vide.')
                                ->success()
                                ->send();
                        }),
            ])
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Supprimer la sélection'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ChemPharmacyResource\RelationManagers\OrdersRelationManager::class,
            // … autres relation managers si tu en as
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemPharmacies::route('/'),
            'create' => Pages\CreateChemPharmacy::route('/create'),
            'edit'   => Pages\EditChemPharmacy::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'fournisseur']) ?? false;
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $u = Auth::user();

        if ($u?->hasRole('fournisseur')) {
            $data['user_id']     = $u->id;
            $data['supplier_id'] = $u->supplier?->id;
        }

        return $data;
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
        return 'warning'; // ou 'success', 'warning', etc.
    }

}
