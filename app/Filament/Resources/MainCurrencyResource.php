<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MainCurrency;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SelectColumn;
use App\Filament\Resources\MainCurrencyResource\Pages;

class MainCurrencyResource extends Resource
{
    protected static ?string $model = MainCurrency::class;

    // Icône du menu
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $navigationLabel = 'Monnaies';
    protected static ?string $modelLabel      = 'Monnaie';
protected static ?string $pluralModelLabel = 'Monnaies';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make([
                Section::make("Formulaire pour ajouter une catégorie")->schema([
                    TextInput::make('created_by')
                            ->label('Créé par (ID utilisateur)')
                            ->default(auth()->id())
                            ->disabled()   // visible mais non modifiable
                            ->dehydrated() // sera envoyé dans la requête
                            ->columnSpan(12)
                            ->required(),
                    Select::make('status')
                        ->label('Statut')
                        ->options([
                            1 => 'Actif',
                            0 => 'Inactif',
                        ])
                        ->default(1)
                        ->required()
                        ->columnSpan(6),

                    TextInput::make('name')
                        ->label('Nom')
                        ->placeholder('Franc Congolais, Dollar US…')
                        ->maxLength(255)
                        ->required()
                        ->columnSpan(6),

                    TextInput::make('code')
                        ->label('Code ISO')
                        ->placeholder('CDF, USD, EUR…')
                        ->rules(['regex:/^[A-Z]{3}$/']) // 3 lettres majuscules
                        ->helperText('Code ISO à 3 lettres (ex. USD, CDF).')
                        ->maxLength(3)
                        ->required()
                        ->columnSpan(6)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                        ->afterStateUpdated(fn($state, callable $set) => $set('code', strtoupper((string) $state))),

                    TextInput::make('exchange_rate')
                        ->label('Taux vers USD')
                        ->helperText("Taux de 1 unité de cette monnaie → USD (ex: 0.92 pour l'EUR).")
                        ->numeric()
                        ->step('0.0001')
                        ->minValue(0.0000001)
                        ->required()
                        ->columnSpan(6),
                ])->columnS(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(),

                BadgeColumn::make('status')
                    ->label('Statut')
                ->formatStateUsing(fn (int $state) => $state === 1 ? 'Actif' : 'Inactif')
                ->colors([
                    'success' => fn ($state) => (int) $state === 1,
                    'danger'  => fn ($state) => (int) $state === 0,
                ]),
                SelectColumn::make('status')
                ->label('Modifier')
                ->options([
                    1 => 'Actif',
                    0 => 'Inactif',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    Notification::make()
                        ->title('Statut mis à jour')
                        ->body("La monnaie « {$record->name} » est maintenant " . ($state ? 'Actif' : 'Inactif') . '.')
                        ->success()
                        ->send();
                }),

                TextColumn::make('name')
                    ->label('Nom')->searchable()->sortable(),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('exchange_rate')
                    ->label('Taux → USD')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 4, '.', ''))
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListMainCurrencies::route('/'),
            // 'create' => Pages\CreateMainCurrency::route('/create'),
            // 'edit' => Pages\EditMainCurrency::route('/{record}/edit'),
        ];
    }
}
