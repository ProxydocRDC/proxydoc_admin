<?php

namespace App\Filament\Resources;

use App\Filament\Actions\TrashAction;
use App\Filament\Actions\TrashBulkAction;
use App\Filament\Concerns\HasTrashableRecords;
use App\Models\ProxyRefExperienceBand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class ProxyRefExperienceBandResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model = ProxyRefExperienceBand::class;

    protected static ?string $navigationIcon  = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Référentiels';
    protected static ?string $navigationLabel = 'Annéesn d’expérience';
    protected static ?string $modelLabel       = 'Tranche d’expérience';
    protected static ?string $pluralModelLabel = 'Tranches d’expérience';
    protected static ?int    $navigationSort   = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations')
                ->schema([

                    Forms\Components\TextInput::make('label')
                        ->label('Libellé')
                        ->required()
                        ->columnSpan(2)
                        ->maxLength(100),

                    Forms\Components\TextInput::make('min_years')
                        ->label('Années min.')
                        ->numeric()
                        ->columnSpan(2)
                        ->minValue(0)
                        ->required(),

                    Forms\Components\TextInput::make('max_years')
                        ->label('Années max.')
                        ->numeric()
                        ->columnSpan(2)
                        ->minValue(0)
                        ->helperText('Laisser vide si “illimité”.'),

                    Forms\Components\TextInput::make('rate')
                        ->label('Taux (%)')
                        ->numeric()
                        ->columnSpan(2)
                        ->step('0.01')
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),
                ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('min_years')
                    ->label('Min.')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('max_years')
                    ->label('Max.')
                    ->formatStateUsing(fn ($state) => $state ?? '—')
                    ->alignRight()
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
                ...array_filter([static::getTrashFilter()]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                TrashAction::make()->label('Mettre à la corbeille'),
            ])
            ->bulkActions([
                TrashBulkAction::make()->label('Mettre à la corbeille'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ProxyRefExperienceBandResource\Pages\ListProxyRefExperienceBands::route('/'),
            // 'create' => ProxyRefExperienceBandResource\Pages\CreateProxyRefExperienceBand::route('/create'),
            // 'edit'   => ProxyRefExperienceBandResource\Pages\EditProxyRefExperienceBand::route('/{record}/edit'),
        ];
    }
}
