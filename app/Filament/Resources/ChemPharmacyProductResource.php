<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\ChemPharmacyProductsExport;
use App\Imports\ChemPharmacyProductsImport;
use App\Support\Filament\RestrictToSupplier;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
        return $table->columns([
            // ImageColumn::make('image')
            //     ->label('Image')
            //     ->square()
            //     ->size(50)
            //     ->getStateUsing(fn($record) => $record->mediaUrl('image')) // URL finale
            //     ->size(64)
            //     ->square()
            //     ->defaultImageUrl(asset('assets/images/default.jpg')) // 👈 évite l’icône cassée
            //     ->openUrlInNewTab()
            //     ->url(fn($record) => $record->mediaUrl('image', ttl: 5)), // clic = grande image,
            ImageColumn::make('image')
    ->label('Image')
    ->square()
    ->size(64)
    // miniature signée (image propre si présente, sinon image du produit)
    ->getStateUsing(fn ($record) => $record->displayImageUrl(10))
    ->defaultImageUrl(asset('assets/images/default.jpg'))
    ->openUrlInNewTab()
    // clic : version avec TTL plus long
    ->url(fn ($record) => $record->displayImageUrl(60)),

            TextColumn::make('pharmacy.name')
                ->label('Pharmacie')
                ->sortable()
                ->searchable(),

            TextColumn::make('product.name')
                ->label('Produit')
                ->sortable()
                ->searchable(),

            TextColumn::make('lot_ref')
                ->label('Lot Ref'),

            TextColumn::make('origin_country')
                ->label('Pays Origine'),

            TextColumn::make('expiry_date')
                ->label('Expiration')
                ->date('d/m/Y'),

            TextColumn::make('sale_price')
                ->label('Prix Vente')
                ->money(fn($record) => $record->currency),

            TextColumn::make('stock_qty')
                ->label('Stock'),

            TextColumn::make('created_by')
                ->label('Créé par')
                ->formatStateUsing(fn($state) => \App\Models\User::find($state)?->name ?? '—'),
        ])
            ->defaultSort('id', 'desc')
            ->filters([])
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
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Supprimer sélection'),
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
            'index'  => Pages\ListChemPharmacyProducts::route('/'),
            'create' => Pages\CreateChemPharmacyProduct::route('/create'),
            'edit'   => Pages\EditChemPharmacyProduct::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'fournisseur']) ?? false;
    }
}
