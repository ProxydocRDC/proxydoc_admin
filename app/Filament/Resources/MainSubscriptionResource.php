<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MainSubscription;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\MainSubscriptionResource\Pages;

class MainSubscriptionResource extends Resource
{
    protected static ?string $model = MainSubscription::class;

    /** Menu & libellés */
    protected static ?string $navigationIcon   = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup  = 'Facturation';
    protected static ?string $navigationLabel  = 'Abonnements';
    protected static ?string $modelLabel       = 'Abonnement';
    protected static ?string $pluralModelLabel = 'Abonnements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire")->schema([
                        TextInput::make('created_by')
                            ->label('Créé par (ID utilisateur)')
                            ->default(auth()->id())
                            ->disabled()   // visible mais non modifiable
                            ->dehydrated() // sera envoyé dans la requête
                            ->columnSpan(12)
                            ->required(),
                        Select::make('status')
                            ->label('Statut')
                            ->options([1 => 'Actif', 0 => 'Inactif'])
                            ->default(1)
                            ->required()
                            ->columnSpan(5),

                        TextInput::make('name')
                            ->label('Nom')
                            ->placeholder('Starter, Pro, Entreprise…')
                            ->maxLength(255)
                            ->required()
                            ->columnSpan(7),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpan(12),

                        TextInput::make('periodicity')
                            ->label('Périodicité (mois)')
                            ->numeric()
                            ->minValue(1)
                            ->step(1)
                            ->required()
                            ->helperText('Nombre de mois par cycle (ex : 1, 3, 12).')
                            ->columnSpan(4),

                        TextInput::make('price')
                            ->label('Prix')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->required()
                            ->prefix('Montant')
                            ->columnSpan(6),

                        // Variante simple (champ libre ISO-4217)
                        TextInput::make('currency')
                            ->label('Devise')
                            ->placeholder('USD, CDF, EUR…')
                            ->maxLength(3)
                            ->rules(['regex:/^[A-Z]{3}$/'])
                            ->required()
                            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                            ->afterStateUpdated(fn($state, callable $set) => $set('currency', strtoupper((string) $state)))
                            ->columnSpan(2),

                        // Variante si tu veux charger depuis main_currencies (décommente si tu as le modèle):
                        // Select::make('currency')
                        //     ->label('Devise')
                        //     ->options(\App\Models\MainCurrency::query()->pluck('code', 'code'))
                        //     ->searchable()
                        //     ->preload()
                        //     ->required()
                        //     ->columnSpan(3),
                    ])->columnS(12),
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

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (int $state) => $state === 1 ? 'Actif' : 'Inactif')
                    ->colors([
                        'success' => fn ($state) => (int)$state === 1,
                        'danger'  => fn ($state) => (int)$state === 0,
                    ])
                    ->sortable(),

                // Colonne d’édition inline du statut + toast
                SelectColumn::make('status')
                    ->label('Modifier')
                    ->options([1 => 'Actif', 0 => 'Inactif'])
                    ->afterStateUpdated(function ($record, $state) {
                        Notification::make()
                            ->title('Statut mis à jour')
                            ->body("L’abonnement « {$record->name} » est maintenant " . ($state ? 'Actif' : 'Inactif') . '.')
                            ->success()
                            ->send();
                    }),

                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periodicity')
                    ->label('Périodicité')
                    ->formatStateUsing(fn ($state) => "{$state} mois")
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Prix')
                    ->formatStateUsing(fn ($state, $record) => number_format((float)$state, 2, '.', ' ') . ' ' . strtoupper($record->currency))
                    ->alignRight()
                    ->sortable(),

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
            'index'  => Pages\ListMainSubscriptions::route('/'),
            'create' => Pages\CreateMainSubscription::route('/create'),
            'edit'   => Pages\EditMainSubscription::route('/{record}/edit'),
        ];
    }
       /** Auto set des auteurs */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }
}
