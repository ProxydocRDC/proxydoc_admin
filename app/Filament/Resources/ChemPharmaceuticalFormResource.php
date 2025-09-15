<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChemPharmaceuticalFormResource\Pages;
use App\Models\ChemPharmaceuticalForm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ChemPharmaceuticalFormResource extends Resource
{
    protected static ?string $model = ChemPharmaceuticalForm::class;

    /** Navigation */
    protected static ?string $navigationIcon   = 'heroicon-o-beaker';
    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?string $navigationLabel  = 'Formes galéniques';
    protected static ?string $modelLabel       = 'Forme galénique';
    protected static ?string $pluralModelLabel = 'Formes galéniques';
    protected static ?int $navigationSort      = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make([
                Forms\Components\Section::make('Formulaire – forme galénique')->schema([
                    Forms\Components\Hidden::make('created_by')->default(Auth::id()),
                    Forms\Components\TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('description')
                        ->label('Description')
                        ->maxLength(100),
                    Forms\Components\Toggle::make('status')
                        ->label('Actif ?')
                        ->default(1)
                        ->onColor('success')
                        ->offColor('danger'),
                ])->columns(3),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->toggleable(),



                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Actif ?')
                    ->options([1 => 'Actif', 0 => 'Inactif']),
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    /** Pour que le filtre "Trashed" fonctionne correctement */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemPharmaceuticalForms::route('/'),
            'create' => Pages\CreateChemPharmaceuticalForm::route('/create'),
            // 'view'   => Pages\ViewChemPharmaceuticalForm::route('/{record}'),
            'edit'   => Pages\EditChemPharmaceuticalForm::route('/{record}/edit'),
        ];
    }

    /** Remplissage auto des champs techniques */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['updated_by'] = $data['updated_by'] ?? Auth::id();
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
