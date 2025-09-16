<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\ChemOrder;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use App\Support\Filament\RestrictToOwner;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\ChemOrderResource\Pages;
use App\Support\Filament\RestrictToSupplier;

class ChemOrderResource extends Resource
{
    // use RestrictToSupplier;
    protected static ?string $model = ChemOrder::class;

    /** Menu & libellés */
    protected static ?string $navigationIcon   = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup  = 'Ventes';
    protected static ?string $navigationLabel  = 'Commandes';
    protected static ?string $modelLabel       = 'Commande';
    protected static ?string $pluralModelLabel = 'Commandes';

    protected static function supplierOwnerPath(): string
    {
        return 'pharmacy.supplier_id';
    }

    // ownerColumn() reste 'created_by'

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        return $data;
    }
    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make([
                Section::make('Métadonnées')->schema([
                    Hidden::make('created_by')->default(Auth::id()),

                    Select::make('status')
                        ->label('Statut (technique)')
                        ->options([1 => 'Actif', 0 => 'Inactif'])
                        ->default(1)
                        ->helperText("Statut interne d'activation de l'enregistrement."),

                    Select::make('order_status')
                        ->label('Statut commande')
                        ->options([
                            'created'  => 'Créée',
                            'pending'  => 'En attente',
                            'paid'     => 'Payée',
                            'canceled' => 'Annulée',
                            'accepted' => 'Acceptée',
                        ])
                        ->required()
                        ->helperText('État commercial de la commande.'),
                ])->columns(3),

                Section::make('Parties & références')->schema([
                    Select::make('customer_id')
                        ->label('Client')
                        ->relationship('customer', 'firstname') // adapte si ton User a un autre champ
                        ->searchable()->preload()->required()
                        ->helperText('Utilisateur qui passe la commande.'),

                    Select::make('pharmacy_id')
                        ->label('Pharmacie (optionnel)')
                        ->relationship('pharmacy', 'name')
                        ->searchable()->preload()
                        ->helperText('Pharmacie liée à la commande, si applicable.'),
                ])->columns(2),

                Section::make('Montants')->schema([
                    TextInput::make('amount')
                        ->label('Montant produits')
                        ->numeric()->minValue(0)->step('0.01')->required()
                        ->helperText('Somme des lignes de produits (hors taxe & livraison).')
                        ->columnSpan(3),

                    TextInput::make('tax')
                        ->label('Taxe')
                        ->numeric()->minValue(0)->step('0.01')->required()
                        ->helperText('Montant total des taxes appliquées.')
                        ->columnSpan(3),

                    TextInput::make('delivery_costs')
                        ->label('Frais de livraison')
                        ->numeric()->minValue(0)->step('0.01')
                        ->helperText('Frais ajoutés pour la livraison (facultatif).')
                        ->columnSpan(3),

                    TextInput::make('total_amount')
                        ->label('Total à payer')
                        ->numeric()->minValue(0)->step('0.01')->required()
                        ->helperText('Montant final : produits + taxe + livraison.')
                        ->columnSpan(3),
                ])->columns(12),

                Section::make('Pièces & remarques')->schema([
                    FileUpload::make('prescription')
                        ->label("Ordonnances (images/PDF)")
                        ->multiple()
                        // ->directory('orders/prescriptions')
                        ->disk('s3') // Filament uploade direct vers S3
                            ->directory('prescriptions')
                            ->visibility('private')
                        ->enableDownload()
                        ->enableOpen()
                        ->imageEditor() 
                        ->helperText('Téléversez une ou plusieurs pièces (images/PDF). Stockées en JSON.'),

                    Textarea::make('note')
                        ->label('Note')
                        ->maxLength(1000)
                        ->helperText('Commentaire interne ou note du client.')
                        ->rows(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('customer.name')
                //     ->label('Client')
                //     ->searchable(),

                // TextColumn::make('pharmacy.name')
                //     ->label('Pharmacie')
                //     ->toggleable(),
TextColumn::make('customer.firstname')   // pas "customer.name" si tu n'as pas ce champ
    ->label('Client')
    ->searchable(),

TextColumn::make('pharmacy.name')        // ✅ cohérent avec l’alias ajouté
    ->label('Pharmacie')
    ->toggleable(),
                BadgeColumn::make('order_status')
                    ->label('Statut')
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'created'                            => 'Créée',
                        'pending'                            => 'En attente',
                        'paid'                               => 'Payée',
                        'canceled'                           => 'Annulée',
                        'accepted'                           => 'Acceptée',
                        default                              => $state,
                    })
                    ->color(fn(string $state) => match ($state) {
                        'created'                 => 'gray',
                        'pending'                 => 'warning',
                        'paid'                    => 'success',
                        'canceled'                => 'danger',
                        'accepted'                => 'info',
                        default                   => 'secondary',
                    }),

                TextColumn::make('amount')
                    ->label('Produits')
                // ->formatStateUsing(fn ($v) => number_format((float) $v, 2, '.', ' '))
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ') . ' ' . ($record->currency ?? 'USD')
                    )
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('tax')
                    ->label('Taxe')
                // ->formatStateUsing(fn ($v) => number_format((float) $v, 2, '.', ' '))
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.')
                    )
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('delivery_costs')
                    ->label('Livraison')
                // ->formatStateUsing(fn ($v) => $v === null ? '—' : number_format((float) $v, 2, '.', ' '))
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ') . ' ' . ($record->currency ?? 'USD')
                    )
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                // ->formatStateUsing(fn ($v) => number_format((float) $v, 2, '.', ' '))
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ') . ' ' . ($record->currency ?? 'USD')
                    )
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('order_status')
                    ->label('Statut commande')
                    ->options([
                        'created'  => 'Créée',
                        'pending'  => 'En attente',
                        'paid'     => 'Payée',
                        'canceled' => 'Annulée',
                        'accepted' => 'Acceptée',
                    ]),
                SelectFilter::make('status')
                    ->label('Actif ?')
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
            ChemOrderResource\RelationManagers\ItemsRelationManager::class,
            ChemOrderResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemOrders::route('/'),
            'create' => Pages\CreateChemOrder::route('/create'),
            'view'   => ChemOrderResource\Pages\ViewChemOrder::route('/{record}'),
            'edit'   => Pages\EditChemOrder::route('/{record}/edit'),
        ];
    }
}
