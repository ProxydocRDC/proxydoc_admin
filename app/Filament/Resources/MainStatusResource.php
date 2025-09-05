<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\MainStatus;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColorColumn;
use Filament\Forms\Components\ColorPicker;
use App\Filament\Resources\MainStatusResource\Pages;

class MainStatusResource extends Resource
{
    protected static ?string $model = MainStatus::class;

    /** Menu & libellés */
    protected static ?string $navigationIcon   = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?string $navigationLabel  = 'Statuts';
    protected static ?string $modelLabel       = 'Statut';
    protected static ?string $pluralModelLabel = 'Statuts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire pour ajouter un status ")->schema([
                        Hidden::make('created_by')
                            ->default(Auth::id()),
                        TextInput::make('status_name')
                            ->label('Nom du statut')
                            ->placeholder('Publié, Brouillon, Archivé…')
                            ->maxLength(255)
                            ->columnSpan(6)
                            ->required(),

                        Textarea::make('status_description')
                            ->label('Description')
                            ->maxLength(1000)
                            ->columnSpan(6)
                            ->rows(3)
                            ->nullable(),

                        // Icône Heroicons (nom, ex: heroicon-o-check, heroicon-o-clock)
                        TextInput::make('icon')
                            ->label('Icône (Heroicons)')
                            ->placeholder('heroicon-o-check')
                            ->helperText('Nom Heroicons v2, ex. heroicon-o-check, heroicon-o-clock.')
                            ->maxLength(45)
                            ->columnSpan(6)
                            ->nullable(),

                        // Couleur du statut
                        ColorPicker::make('color')
                            ->label('Couleur')
                            ->nullable()
                            ->columnSpan(6)
                            ->helperText('Couleur CSS (hex) utilisée pour l’affichage.'),
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

            IconColumn::make('icon')
                ->label('Icône')
                ->icon(fn ($state) => filled($state) ? $state : 'heroicon-o-check')
                ->tooltip(fn ($record) => $record->status_name ?? null),

            TextColumn::make('status_name')
                ->label('Nom')
                ->sortable()
                ->searchable(),

            TextColumn::make('status_description')
                ->label('Description')
                ->limit(40)
                ->toggleable(),

            ColorColumn::make('color')
                ->label('Couleur')
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
            // Ajoute tes filtres ici si besoin
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

        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMainStatuses::route('/'),
            // 'create' => Pages\CreateMainStatus::route('/create'),
            // 'edit'   => Pages\EditMainStatus::route('/{record}/edit'),
        ];
    }
    /** Remplit automatiquement created_by à la création */
    // public static function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['created_by'] = auth()->id();
    //     return $data;
    // }
}
