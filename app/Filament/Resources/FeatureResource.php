<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureResource\Pages;
use App\Models\Feature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{Group, Section, TextInput, Textarea, Toggle, Select};
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn};

class FeatureResource extends \Filament\Resources\Resource
{
    protected static ?string $model = Feature::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?string $navigationLabel = 'Fonctionnalités';
    protected static ?int    $navigationSort  = 50;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Fonctionnalité')->schema([
                    TextInput::make('code')->required()->maxLength(50)->columnSpan(3),
                    TextInput::make('name')->label('Nom')->required()->maxLength(100)->columnSpan(3),
                    Textarea::make('description')->rows(3)->columnSpan(3),
                    TextInput::make('category')->label('Catégorie')->maxLength(50)->columnSpan(3),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')->label('Code')->searchable(),
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('category')->label('Catégorie'),
                IconColumn::make('status')->label('Actif')->boolean(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFeatures::route('/'),
            'create' => Pages\CreateFeature::route('/create'),
            'edit'   => Pages\EditFeature::route('/{record}/edit'),
        ];
    }
}
