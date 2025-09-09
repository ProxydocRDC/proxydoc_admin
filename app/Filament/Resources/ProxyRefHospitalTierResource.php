<?php

namespace App\Filament\Resources;

use App\Models\ProxyRefHospitalTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class ProxyRefHospitalTierResource extends Resource
{
    protected static ?string $model = ProxyRefHospitalTier::class;

    protected static ?string $navigationIcon  = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Référentiels';
    protected static ?string $navigationLabel = 'Niveaux d’hôpitaux';
    protected static ?string $modelLabel       = 'Niveau d’hôpital';
    protected static ?string $pluralModelLabel = 'Niveaux d’hôpitaux';
    protected static ?int    $navigationSort   = 41;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations')
                ->schema([                  

                    Forms\Components\TextInput::make('code')
                        ->label('Code')
                        ->columnSpan(1)
                        ->required()
                        ->maxLength(32),

                    Forms\Components\TextInput::make('label')
                        ->label('Libellé')
                        ->required()
                        ->columnSpan(1)
                        ->maxLength(100),

                    Forms\Components\TextInput::make('rate')
                        ->label('Taux (%)')
                        ->numeric()
                        ->columnSpan(1)
                        ->step('0.01')
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rate')
                    ->label('Taux')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', ' ') . ' %')
                    ->alignRight()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => (int) $state === 1 ? 'Actif' : 'Inactif')
                    ->color(fn ($state) => (int) $state === 1 ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Actif ?')
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

    public static function getPages(): array
    {
        return [
            'index'  => ProxyRefHospitalTierResource\Pages\ListProxyRefHospitalTiers::route('/'),
            // 'create' => ProxyRefHospitalTierResource\Pages\CreateProxyRefHospitalTier::route('/create'),
            // 'edit'   => ProxyRefHospitalTierResource\Pages\EditProxyRefHospitalTier::route('/{record}/edit'),
        ];
    }
}
