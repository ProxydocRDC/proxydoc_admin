<?php
namespace App\Filament\Resources;

use App\Filament\Actions\TrashAction;
use App\Filament\Actions\TrashBulkAction;
use App\Filament\Concerns\HasTrashableRecords;
use Filament\Tables;
use App\Models\MainCity;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\MainCityResource\Pages;

class MainCityResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model = MainCity::class;
    /** Menu & libellés */
    protected static ?string $navigationIcon   = 'heroicon-o-map-pin'; // 📍
    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?string $navigationLabel  = 'Villes';
    protected static ?string $modelLabel       = 'Ville';
    protected static ?string $pluralModelLabel = 'Villes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire pour ajouter une cité")->schema([
                        TextInput::make('created_by')
                            ->label('Créé par (ID utilisateur)')
                            ->default(auth()->id())
                            ->disabled()   // visible mais non modifiable
                            ->dehydrated() // sera envoyé dans la requête
                            ->columnSpan(12)
                            ->required(),
                        Select::make('country_id')
                            ->label('Pays')
                            ->relationship('country', 'name_fr') // <— adapte si besoin
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(6),

                        TextInput::make('city')
                            ->label('Ville')
                            ->placeholder('Kinshasa, Lubumbashi…')
                            ->maxLength(200)
                            ->required()
                        // Unicité du nom de ville pour un même pays
                            ->unique(
                                table: 'main_cities',
                                column: 'city',
                                ignoreRecord: true,
                                modifyRuleUsing: function (Rule $rule, callable $get) {
                                    return $rule->where('country_id', $get('country_id'));
                                }
                            ),
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

                TextColumn::make('country.name_fr')   // via la relation
                    ->label('Pays')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('city')
                    ->label('Ville')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Créée le')
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
               SelectFilter::make('country_id')
                    ->label('Pays')
                    ->relationship('country', 'name_fr')
                    ->indicator('Pays'),
                ...array_filter([static::getTrashFilter()]),
            ])
            ->actions([
               EditAction::make()->label('Modifier'),
               TrashAction::make()->label('Mettre à la corbeille'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    TrashBulkAction::make(),
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
            'index'  => Pages\ListMainCities::route('/'),
            // 'create' => Pages\CreateMainCity::route('/create'),
            // 'edit'   => Pages\EditMainCity::route('/{record}/edit'),
        ];
    }
}
