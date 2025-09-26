<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MainCountry;
use App\Models\ChemManufacturer;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\ChemManufacturerResource\Pages;

class ChemManufacturerResource extends Resource
{
    protected static ?string $model = ChemManufacturer::class;

    /** Menu & libellés */
    protected static ?string $navigationIcon   = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?string $navigationLabel  = 'Fabricants';
    protected static ?string $modelLabel       = 'Fabricant';
    protected static ?string $pluralModelLabel = 'Fabricants';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire fabricant")
                        ->schema([
                            Hidden::make('created_by')
                        ->default(Auth::id()),

                            Section::make('Informations')
                                ->schema([
                                    Select::make('status')
                                        ->label('Statut')
                                        ->options([1 => 'Actif', 0 => 'Inactif'])
                                        ->default(1)
                                        ->required()
                                        ->columnSpan(2),

                                    TextInput::make('name')
                                        ->label('Nom du fabricant')
                                        ->required()
                                        ->maxLength(100)
                                        ->columnSpan(6),

                                    // Pays par code ISO (lié à main_countries si présent)
                                    Select::make('country')
                                        ->label('Pays (code ISO-2/3)')
                                        ->options(fn() => MainCountry::query()
                                                ->orderBy('name_fr')
                                                ->pluck('name_fr', 'code') // ex: ['CD' => 'Congo (RDC)']
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->helperText("Le champ en base est un code (ex: 'CD', 'FR', 'US').")
                                        ->nullable()
                                        ->columnSpan(4),

                                    Textarea::make('description')
                                        ->label('Description')
                                        ->maxLength(1000)
                                        ->rows(3)
                                        ->columnSpan(12),
                                ])
                                ->columns(12),
                        ])
                        ->columns(12),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country')
                    ->label('Pays')
                    ->formatStateUsing(function ($state) {
                        if (blank($state)) {
                            return '-';
                        }

                        // If you have main_countries table:
                        $name = MainCountry::query()->where('code', $state)->value('name_fr');

                        return $name ? "{$state} — {$name}" : $state;
                    })
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($s) => (int) $s === 1 ? 'Actif' : 'Inactif')
                    ->colors([
                        'success' => fn($s) => (int) $s === 1,
                        'danger'  => fn($s)  => (int) $s === 0,
                    ])
                    ->sortable(),

                // Edition inline du statut + toast
                SelectColumn::make('status')
                    ->label('Modifier')
                    ->options([1 => 'Actif', 0 => 'Inactif'])
                    ->afterStateUpdated(function ($record, $state) {
                        Notification::make()
                            ->title('Statut mis à jour')
                            ->body("Le fabricant « {$record->name} » est maintenant " . ((int) $state === 1 ? 'Actif' : 'Inactif') . '.')
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
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([1 => 'Actif', 0 => 'Inactif']),
                SelectFilter::make('country')
                    ->label('Pays')
                    ->options(fn() => MainCountry::query()
                            ->orderBy('name_fr')->pluck('name_fr', 'code')),
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
            'index'  => Pages\ListChemManufacturers::route('/'),
            // 'create' => Pages\CreateChemManufacturer::route('/create'),
            // 'edit'   => Pages\EditChemManufacturer::route('/{record}/edit'),
        ];
    }
     public static function getNavigationBadge(): ?string
    {
        // total global de lignes “produit en pharmacie”
        $base = ChemManufacturer::query();

        return (string) $base->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning'; // ou 'success', 'warning', etc.
    }
}
