<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ChemSupplierResource\Pages;
use App\Models\ChemSupplier;
use App\Models\User;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ChemSupplierResource extends Resource
{
    protected static ?string $model = ChemSupplier::class;

    /** Menu & labels */
    protected static ?string $navigationIcon   = 'heroicon-o-truck';
    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?string $navigationLabel  = 'Fournisseurs';
    protected static ?string $modelLabel       = 'Fournisseur';
    protected static ?string $pluralModelLabel = 'Fournisseurs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire fournisseur")
                        ->schema([
                            Hidden::make('created_by')
                                ->label('Créé par (ID utilisateur)')
                                ->default(Auth::id())
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->columnSpan(12),

                            Section::make('Informations générales')
                                ->schema([

                                    TextInput::make('company_name')
                                        ->label('Raison sociale')
                                        ->maxLength(256)
                                        ->required()
                                        ->columnSpan(6),

                                    TextInput::make('fullname')
                                        ->label('Nom du gérant / propriétaire')
                                        ->maxLength(256)
                                        ->columnSpan(6),

                                    TextInput::make('phone')
                                        ->label('Téléphone')
                                        ->maxLength(20)
                                        ->tel()
                                        ->columnSpan(4),

                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->maxLength(256)
                                        ->columnSpan(4),

                                    TextInput::make('address')
                                        ->label('Adresse')
                                        ->maxLength(500)
                                        ->columnSpan(4),

                                    Textarea::make('description')
                                        ->label('Description')
                                        ->maxLength(1000)
                                        ->rows(3)
                                        ->columnSpan(12),
                                ])
                                ->columns(12),

                            Section::make('Informations légales')
                                ->schema([
                                    TextInput::make('rccm')->label('RCCM')->maxLength(100)->columnSpan(4),
                                    TextInput::make('id_nat')->label('ID Nat')->maxLength(100)->columnSpan(4),
                                    TextInput::make('tax_number')->label('N° Impôt')->maxLength(100)->columnSpan(4),
                                ])
                                ->columns(12),

                            Section::make('Compte & Logo')
                                ->schema([
                                    Select::make('user_id')
                                        ->label('Utilisateur lié')
                                        ->options(User::query()->pluck('firstname', 'id')) // ou firstname
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(6),

                                    FileUpload::make('logo')
                                        ->label('Logo')
                                        ->image()
                                        ->imagePreviewHeight('150')
                                        ->maxSize(2048)
                                        ->disk('s3')
                                        ->visibility('private')
                                        ->directory('suppliers')
                                    // ->disk('public')
                                    // ->visibility('public')
                                        ->columnSpan(6),

                                ])
                                ->columns(6),
                        ])
                        ->columns(12),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo') // colonne réelle = 'image'
                    ->label('Logo')
                    ->getStateUsing(fn($record) => $record->mediaUrl('logo')) // URL finale
                    ->size(64)
                    ->square()
                    ->defaultImageUrl(asset('assets/images/default.jpg')) // 👈 évite l’icône cassée
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->mediaUrl('logo', ttl: 5)), // clic = grande image

                TextColumn::make('company_name')
                    ->label('Société')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('fullname')
                    ->label('Gérant / Proprio')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('phone')->label('Téléphone'),
                TextColumn::make('email')->label('Email'),

                TextColumn::make('address')
                    ->label('Adresse')
                    ->limit(30),

                TextColumn::make('rccm')->label('RCCM')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('id_nat')->label('ID Nat')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tax_number')->label('N° Impôt')->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($s) => (int) $s === 1 ? 'Actif' : 'Inactif')
                    ->colors([
                        'success' => fn($s) => (int) $s === 1,
                        'danger'  => fn($s)  => (int) $s === 0,
                    ])
                    ->sortable(),

                SelectColumn::make('status')
                    ->label('Modifier')
                    ->options([1 => 'Actif', 0 => 'Inactif'])
                    ->afterStateUpdated(function ($record, $state) {
                        \Filament\Notifications\Notification::make()
                            ->title('Statut mis à jour')
                            ->body("Le fournisseur « {$record->company_name} » est maintenant " . ((int) $state === 1 ? 'Actif' : 'Inactif') . '.')
                            ->success()
                            ->send();
                    }),

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
                SelectFilter::make('status')->label('Statut')->options([1 => 'Actif', 0 => 'Inactif']),
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
            'index'  => Pages\ListChemSuppliers::route('/'),
            'create' => Pages\CreateChemSupplier::route('/create'),
            'edit'   => Pages\EditChemSupplier::route('/{record}/edit'),
        ];
    }
     public static function getNavigationBadge(): ?string
    {
        // total global de lignes “produit en pharmacie”

        $base = ChemSupplier::query();


        return (string) $base->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success'; // ou 'success', 'warning', etc.
    }
}
