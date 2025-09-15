<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\MainCity;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MainCountry;
use Filament\Resources\Resource;
use Filament\Forms\Components\{Group, Section, TextInput, Textarea, Toggle, Select, Hidden};
use Filament\Tables\Columns\{TextColumn, BadgeColumn, IconColumn};
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\MainZoneResource\Pages;

class MainZoneResource extends Resource
{
    protected static ?string $navigationIcon   = 'heroicon-o-map';
    protected static ?string $navigationLabel  = 'Zones de livraison';
    protected static ?string $modelLabel       = 'Zone';
    protected static ?string $pluralModelLabel = 'Zones';
    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?int $navigationSort      = 50;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Informations')->schema([
                    Hidden::make('created_by')->default(fn() => Auth::id()),

                    TextInput::make('name')
                        ->label('Nom de la zone')
                        ->required()
                        ->columnSpan(4)
                        ->maxLength(255),

                    Select::make('country')
                        ->label('Pays')
                        ->columnSpan(4)
                        ->options(fn() =>
                            MainCountry::query()
                                ->orderBy('name_fr')
                                ->pluck('name_fr','id')
                        )
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(fn(Set $set) => $set('city', null))
                        ->required(),

                    Select::make('city')
                        ->label('Ville')
                        ->columnSpan(4)
                         ->options(fn(Get $get) =>
                            $get('country')
                            ? MainCity::where('country_id', $get('country'))
                                ->orderBy('city')->pluck('city', 'city')
                            : []
                        )
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->disabled(fn(Get $get) =>
                            ! $get('country') ||
                            ! MainCity::where('country_id', $get('country'))->exists()
                        )
                        ->hint(fn(Get $get) =>
                            ! $get('country') ? 'Sélectionnez d’abord un pays.'
                            : (MainCity::where('country_id', $get('country'))->exists()
                                ? 'Provinces disponibles.' : 'Aucune province pour ce pays.')
                        )
                        ->hintColor(fn(Get $get) =>
                            ! $get('country') ? 'danger'
                            : (MainCity::where('country_id', $get('country'))->exists() ? 'success' : 'danger')
                        )
                        ->required(),

                    TextInput::make('distance')
                        ->columnSpan(4)
                        ->label('Distance (Km)')
                        ->numeric()->minValue(0)
                        ->default(0)
                        ->required(),

                    TextInput::make('delivery_fee')
                        ->columnSpan(4)
                        ->label('Frais de livraison')
                        ->numeric()->minValue(0)->step('0.01')
                        ->default(0)
                        ->required(),

                    Select::make('currency')
                        ->columnSpan(4)
                        ->label('Devise')
                        ->options([
                            'USD' => 'USD',
                            'CDF' => 'CDF',
                        ])
                        ->default('USD')
                        ->required(),
                    Textarea::make('description')
                        ->label('Description')->columnSpan(12)->rows(3),

                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->label('Zone')->searchable()->sortable(),
                TextColumn::make('city')->label('Ville')->toggleable(),
                TextColumn::make('country')->label('Pays')
                    ->formatStateUsing(fn($state, $record) => $record->name_fr ? "{$record->name_fr} ({$state})" : $state)
                    ->toggleable(),
                TextColumn::make('distance')->label('Km')->sortable()->alignRight(),
                TextColumn::make('delivery_fee')->label('Frais')
                    ->money(fn($record) => $record->currency ?? 'USD')
                    ->sortable()
                    ->alignRight(),
                BadgeColumn::make('currency')->label('Devise')->color('gray'),
                IconColumn::make('status')->label('Actif')->boolean(),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('country')
                    ->label('Pays')
                    ->options(fn() => MainCountry::orderBy('name_fr')->pluck('name_fr')),
                SelectFilter::make('status')
                    ->label('Actif ?')
                    ->options([1 => 'Actif', 0 => 'Inactif']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
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
             'index'  => Pages\ListMainZones::route('/'),
            'create' => Pages\CreateMainZone::route('/create'),
            'view'   => Pages\ViewMainZone::route('/{record}'),
            'edit'   => Pages\EditMainZone::route('/{record}/edit'),
            ];
        }
    }
