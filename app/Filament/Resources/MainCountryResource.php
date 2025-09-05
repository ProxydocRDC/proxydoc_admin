<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MainCountry;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\MainCountryResource\Pages;

class MainCountryResource extends Resource
{
    protected static ?string $model = MainCountry::class;

    protected static ?string $navigationIcon   = 'heroicon-o-globe-europe-africa';
    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?string $navigationLabel  = 'Pays';
    protected static ?string $modelLabel       = 'Pays';
    protected static ?string $pluralModelLabel = 'Pays';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire pour ajouter un Pays")->schema([
 Hidden::make('created_by')
                        ->default(Auth::id()),
                        TextInput::make('code')
                            ->label('Code ISO 2')
                            ->maxLength(2)
                            ->columnSpan(6)
                            ->default(null)
                            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                            ->afterStateUpdated(fn($state, callable $set) => $set('code', strtoupper((string) $state))),

                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                1 => 'Actif',
                                0 => 'Inactif',
                            ])
                            ->default(1)
                            ->columnSpan(6)
                            ->required(),

                        TextInput::make('name_en')
                            ->label('Nom (Anglais)')
                            ->maxLength(80)
                            ->columnSpan(3)
                            ->required(),

                        TextInput::make('name_fr')
                            ->label('Nom (Français)')
                            ->maxLength(80)
                            ->columnSpan(3)
                            ->required(),

                        TextInput::make('code_tel')
                            ->label('Indicatif Téléphonique')
                            ->placeholder('+243')
                            ->columnSpan(6)
                            ->maxLength(8),
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
                    ->formatStateUsing(fn($state) => $state == 1 ? 'Actif' : 'Inactif')
                    ->colors([
                        'success' => fn($state) => $state == 1,
                        'danger'  => fn($state)  => $state == 0,
                    ])
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Code ISO')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_fr')
                    ->label('Nom FR')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_en')
                    ->label('Nom EN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code_tel')
                    ->label('Indicatif')
                    ->searchable(),
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
            'index'  => Pages\ListMainCountries::route('/'),
            // 'create' => Pages\CreateMainCountry::route('/create'),
            // 'edit'   => Pages\EditMainCountry::route('/{record}/edit'),
        ];
    }
}
