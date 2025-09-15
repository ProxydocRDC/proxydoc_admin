<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ChemPharmacyResource\Pages;
use App\Models\ChemPharmacy;
use App\Support\Filament\RestrictToSupplier;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ChemPharmacyResource extends Resource
{
    use RestrictToSupplier;
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
                                                    fn($q) => $q->whereKey(Auth::id()))
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
                                                    fn($q) => $q->whereKey(Auth::user()->supplier?->id ?? 0))
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
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->height(40)
                    ->getStateUsing(fn($record) => $record->mediaUrl('logo')) // URL finale
                    ->size(64)
                    ->square()
                    ->defaultImageUrl(asset('images/PROFI-TIK.jpg'))  // 👈 évite l’icône cassée
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->mediaUrl('logo', ttl: 5)), // clic = grande image,,

                TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('phone')->label('Téléphone'),
                TextColumn::make('email')->label('Email'),

                TextColumn::make('address')
                    ->label('Adresse')
                    ->limit(30),

                TextColumn::make('user.fullname') // adapte le champ d’affichage
                    ->label('Propriétaireb')
                    ->toggleable(),

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
            ->actions([
                Tables\Actions\Action::make('orders')
                    ->label('Commandes')
                    ->icon('heroicon-o-receipt-percent')
                    ->url(fn($record) =>
                        static::getUrl('edit', ['record' => $record]) . '#relationManager=orders'
                    ),
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

}
