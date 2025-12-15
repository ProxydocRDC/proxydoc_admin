<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Query\Builder;
use App\Filament\Resources\SubscriptionPlanResource\Pages;
use Filament\Tables\Columns\{TextColumn, BadgeColumn, IconColumn};
use Filament\Forms\Components\{Group, Section, TextInput, Textarea, Toggle, Select};
use Illuminate\Support\Facades\Auth;

class SubscriptionPlanResource extends \Filament\Resources\Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?string $navigationLabel = 'Plans';
    protected static ?int    $navigationSort  = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Plan')->schema([ 
                    Hidden::make('created_by')->default(fn() => Auth::id()),
                    Hidden::make('updated_by')->default(fn() => Auth::id())->dehydrated(),
                    Select::make('type')->label('Type')
                        ->options([
                            'personal'   => 'Personnel',
                            'family'     => 'Famille',
                            'enterprise' => 'Entreprise',
                        ])->required()
                        ->columnSpan(3),

                    TextInput::make('name')->label('Nom')->required()->maxLength(255)->columnSpan(3),

                    Textarea::make('description')->label('Description')->rows(3)->columnSpan(3),

                    TextInput::make('periodicity')->label('Périodicité (mois)')
                        ->numeric()->minValue(1)->default(1)->required()->columnSpan(3),

                    TextInput::make('price')->label('Prix (cycle)')
                        ->numeric()->minValue(0)->step('0.01')->required()->columnSpan(3),

                    Select::make('currency')->label('Devise')
                        ->options(['USD' => 'USD', 'CDF' => 'CDF', 'EUR' => 'EUR'])
                        ->default('USD')->required()->columnSpan(2),

                    TextInput::make('max_users')->label('Max utilisateurs inclus')
                        ->numeric()->minValue(1)->default(1)->required()->columnSpan(2),
                    TextInput::make('max_appointments')->label('Max consultations inclus')
                        ->numeric()->minValue(1)->default(1)->required()->columnSpan(2),

                    TextInput::make('extra_user_price')->label('Prix / utilisateur supplémentaire')
                        ->numeric()->minValue(0)->step('0.01')->columnSpan(2),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),

                BadgeColumn::make('type')->label('Type')->colors([
                    'primary' => ['personal', 'family', 'enterprise'],
                ])->formatStateUsing(fn (string $state)   => [
                    'personal' => 'Personnel',
                    'family' => 'Famille',
                    'enterprise' => 'Entreprise',
                ][$state] ?? $state),

                TextColumn::make('periodicity')->label('Mois')->alignRight(),
                TextColumn::make('price')->label('Prix')->money(fn ($record) => $record->currency),
                TextColumn::make('max_users')->label('Utilisateurs inclus')->alignRight(),
                IconColumn::make('status')->label('Actif')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Type')->options([
                    'personal' => 'Personnel',
                    'family' => 'Famille',
                    'enterprise' => 'Entreprise',
                ]),
                Tables\Filters\SelectFilter::make('status')->label('Actif ?')->options([1 => 'Actif', 0 => 'Inactif']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit'   => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\SubscriptionPlanResource\RelationManagers\FeaturesRelationManager::class,
        ];
    }

}
