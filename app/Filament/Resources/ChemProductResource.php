<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ChemProductResource\Pages;
use App\Models\ChemProduct;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                            FileUpload::make('images')
                                ->label('Images')
                                ->disk('s3') // Filament uploade direct vers S3
                                ->directory('products')
                                ->visibility('private') // ->visibility('public')                               // ou enlève pour bucket privé
                                ->multiple()
                                ->image()
                                ->imageEditor() // optionnel
                                ->reorderable()
                                ->helperText('Tu peux déposer plusieurs images ; elles sont stockées sur S3.')
                                ->enableDownload()
                                ->enableOpen()
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
            ->columns([
                // Première image si dispo
                ImageColumn::make('images')
                    ->label('Images')
                // renvoie un ARRAY d’URLs pour l’affichage empilé
                    ->getStateUsing(fn($record) => $record->mediaUrls('images'))
                    ->defaultImageUrl(asset('images/PROFI-TIK.jpg'))
                    ->circular()
                    ->stacked()
                    ->limit(2)
                    ->limitedRemainingText()
                    ->height(44), // ou ->size(44)
                              // ⚠️ ne PAS mettre ->url() ici, car on a plusieurs images
                              // ⚠️ inutile de ->disk() si tu fournis des URLs complètes

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

                TextColumn::make('manufacturer.name')
                    ->label('Fabricant')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('form.name')
                    ->label('Forme')
                    ->toggleable(),

                TextColumn::make('strength')
                    ->label('Dosage')
                    ->formatStateUsing(fn($s, $r) => $s ? rtrim(rtrim(number_format((float) $s, 2, '.', ''), '0'), '.') . ' ' . ($r->unit ?? '') : '—')
                    ->toggleable(),

                // lecture : badge “Actif/Inactif”
                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($s) => (int) $s === 1 ? 'Actif' : 'Inactif')
                    ->colors([
                        'success' => fn($s) => (int) $s === 1,
                        'danger'  => fn($s)  => (int) $s === 0,
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
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
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
}
