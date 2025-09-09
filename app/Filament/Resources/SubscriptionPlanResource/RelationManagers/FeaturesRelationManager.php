<?php

namespace App\Filament\Resources\SubscriptionPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\BelongsToManyRelationManager;

class FeaturesRelationManager extends RelationManager
{
    /** Relation Eloquent sur le modèle ProxySubscriptionPlan */
    protected static string $relationship = 'features';

    protected static ?string $title = 'Fonctionnalités';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        // Champs du pivot (proxy_plan_features) accessibles ici par leur nom direct
        return $form->schema([
            Forms\Components\Toggle::make('is_included')
                ->label('Incluse ?')
                ->default(true)
                ->inline(false),

            Forms\Components\Select::make('status')
                ->label('Statut')
                ->options([1 => 'Actif', 0 => 'Inactif'])
                ->default(1),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->toggleable(),
                TextColumn::make('name')->label('Fonctionnalité')->searchable(),

                // Colonnes pivot : utilisez la notation "pivot.xxx"
                IconColumn::make('pivot.is_included')->label('Incluse ?')->boolean(),

                TextColumn::make('pivot.status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state) => (int) $state === 1 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => (int) $state === 1 ? 'Actif' : 'Inactif'),
            ])
            ->headerActions([
                // Attacher une fonctionnalité existante
                Tables\Actions\AttachAction::make()->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),     // éditer les champs pivot
                Tables\Actions\DetachAction::make(),   // détacher du plan
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
