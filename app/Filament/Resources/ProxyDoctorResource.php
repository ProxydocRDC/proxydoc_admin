<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Set;
use App\Models\ProxyDoctor;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\ProxyDoctorResource\Pages;
use Filament\Tables\Columns\{TextColumn, ToggleColumn, BadgeColumn};
use Filament\Forms\Components\{Group, Section, TextInput, Textarea, Select, Hidden, Toggle, Repeater};

class ProxyDoctorResource extends Resource
{
    protected static ?string $model = ProxyDoctor::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Médecins';
    protected static ?string $modelLabel = 'Médecin';
    protected static ?string $navigationGroup = 'Proxydoc';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Group::make([
                Section::make("Fiche médecin")->schema([
                    Hidden::make('created_by')->default(fn () => Auth::id()),
                    Hidden::make('updated_by')->default(fn () => Auth::id())->dehydrated(),


                    Select::make('user_id')
                        ->label('Utilisateur (compte)')
                        // si tu as le modèle User :
                        ->relationship(name: 'user', titleAttribute: 'firstname')
                        ->relationship(name: 'user', titleAttribute: 'lastname')
                        ->searchable()->preload()->required()
                        ->columnSpan(6),

                    TextInput::make('fullname')
                        ->label('Nom complet')->required()->maxLength(200)->columnSpan(6),

                    // langues (SET en base) -> multiselect
                    Select::make('languages_spoken')
                        ->label('Langues parlées')
                        ->multiple()
                        ->options([
                            'français' => 'Français',
                            'anglais'  => 'Anglais',
                            'lingala'  => 'Lingala',
                            'swahili'  => 'Swahili',
                            'kikongo'  => 'Kikongo',
                            'kiluba'   => 'Kiluba',
                        ])
                         // String -> Array pour l’affichage (quand on édite)
    ->afterStateHydrated(function ($state, Set $set) {
        if (is_string($state)) $set('languages_spoken', explode(',', $state));
    })
    // Array -> String avant sauvegarde
    ->dehydrateStateUsing(fn ($state) => $state ? implode(',', $state) : null)
                        ->columnSpan(4),

                    TextInput::make('education_level')
                        ->label("Niveau d'étude")->required()->maxLength(150)->columnSpan(4),

                    TextInput::make('rating')->numeric()->minValue(0)->maxValue(5)->step('0.1')
                            ->label('Note')->columnSpan(4),

                    Repeater::make('career_history')
                        ->label('Parcours (repeater → stocké en JSON)')
                        ->schema([
                            TextInput::make('position')->label('Poste')->maxLength(150)->required(),
                            TextInput::make('institution')->label('Institution')->maxLength(150)->required(),
                            DatePicker::make('start')->label('Début'),
                            DatePicker::make('end')->label('Fin'),
                        ])
                        ->columns(4)
                        ->collapsed()
                        ->columnSpan(12),


                            Textarea::make('expertise_skills')
                        ->label('Compétences / Spécialisations (JSON ou texte)')
                        ->rows(3)->columnSpan(6),
                    Textarea::make('bio')->label('Bio')->rows(3)->columnSpan(6),

                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                // ToggleColumn::make('status')->label('Actif'),
                TextColumn::make('fullname')->label('Nom')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Email')->searchable(),
                BadgeColumn::make('languages_spoken')->label('Langues')->separator(', ')->limit(30),
                TextColumn::make('rating')->label('Note')->sortable(),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->label('Créé le')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')->label('Actif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id','desc');
    }

   public static function getPages(): array
{
    return [
        'index'  => Pages\ListProxyDoctors::route('/'),
        'create' => Pages\CreateProxyDoctor::route('/create'),
        'view'   => Pages\ViewProxyDoctor::route('/{record}'),
        'edit'   => Pages\EditProxyDoctor::route('/{record}/edit'),
    ];
}


    // Hooks pour created_by / updated_by
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
