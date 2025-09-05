<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ChemPosologyResource\Pages;
use App\Models\ChemPosology;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ChemPosologyResource extends Resource
{
    protected static ?string $model = ChemPosology::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Posologies';
    protected static ?string $pluralLabel     = 'Posologies';
    protected static ?string $modelLabel      = 'Posologie';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make([
                Section::make("Formulaire pour ajouter une posologie")->schema([

                        Hidden::make('created_by')
                            ->default(Auth::id()),
                        Select::make('product_id')
                            ->label('Médicament')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->columnSpan(6)
                            ->required()
                            ->helperText('Sélectionnez le médicament concerné par cette posologie.'),

                        Select::make('target_population')
                            ->label('Population cible')
                            ->options([
                                'neonatal'         => 'Nouveau-né',
                                'infantile'        => 'Bébé (1 mois–2 ans)',
                                'pediatric'        => 'Enfant (2–12 ans)',
                                'adolescent'       => 'Adolescent (12–18 ans)',
                                'adult'            => 'Adulte (18–65 ans)',
                                'senior'           => 'Senior (65 ans et plus)',
                                'pregnant'         => 'Femme enceinte',
                                'lactating'        => 'Femme allaitante',
                                'renal_impaired'   => 'Insuffisance rénale',
                                'hepatic_impaired' => 'Insuffisance hépatique',
                            ])->columnSpan(6)
                            ->required()
                            ->helperText('Indiquez le groupe de patients auquel s’applique cette posologie.'),

                        TextInput::make('posology_text')
                            ->label('Posologie')
                            ->required()->columnSpan(6)
                            ->maxLength(100)
                            ->helperText('Indiquez la posologie exacte, ex. "1 cp × 3/j après repas".'),

                        TextInput::make('max_daily_dose')
                            ->label('Dose maximale quotidienne')
                            ->maxLength(100)->columnSpan(6)
                            ->helperText('Précisez la dose maximale par jour, ex. "60 mg/kg/j".'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(500)->columnSpan(12)
                            ->helperText('Ajoutez des remarques ou précisions importantes pour l’utilisation.'),
                    ])
                    ->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Médicament')->sortable()->searchable(),
                TextColumn::make('target_population')->label('Population cible')->sortable(),
                TextColumn::make('posology_text')->label('Posologie'),
                TextColumn::make('max_daily_dose')->label('Dose max.'),
                TextColumn::make('notes')->label('Notes')->limit(30),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y'),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index'  => Pages\ListChemPosologies::route('/'),
            'create' => Pages\CreateChemPosology::route('/create'),
            'edit'   => Pages\EditChemPosology::route('/{record}/edit'),
        ];
    }
}
