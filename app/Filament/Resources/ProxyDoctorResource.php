<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Support\Sms;
use Filament\Tables;
use Filament\Forms\Set;
use App\Models\ProxyDoctor;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
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
                        ->relationship(name: 'user', titleAttribute: 'Fullname')
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
                        ->columnSpan(6),
                        Select::make('services')
                        ->label('Services proposés')
                        ->relationship('services', 'label')   // proxy_services.label
                        ->multiple()
                        ->preload()
                        ->searchable()
                        // Array -> String avant sauvegarde
                        ->dehydrateStateUsing(fn ($state) => $state ? implode(',', $state) : null)
                        ->columnSpan(6)
                        ->helperText('Choisissez les services offerts par ce médecin.')
                        ->afterStateHydrated(function ($state, Set $set) {
                            if (is_string($state)) $set('services', explode(',', $state));
                        })
                        ->saveRelationshipsUsing(function (ProxyDoctor $doctor, ?array $state) {
                            $state = $state ?? [];
                            // Construire les données pivot : [service_id => ['created_by'=>..., 'updated_by'=>...], ...]
                            $pivotData = collect($state)->mapWithKeys(function ($serviceId) {
                                return [$serviceId => [
                                    'status'     => 1,
                                    'created_by' => Auth::id(),
                                    'updated_by' => Auth::id(),
                                ]];
                            })->all();

                            $doctor->services()->sync($pivotData);
                            }),


                    TextInput::make('education_level')
                        ->label("Niveau d'étude")->required()->maxLength(150)->columnSpan(4),

                    TextInput::make('rating')->numeric()->minValue(0)->maxValue(5)->step('0.1')
                            ->label('Note')->columnSpan(4),
                    TextInput::make('years_experience')->numeric()->minValue(1)
                            ->label('Années d\'expérience')->columnSpan(4),
                    Select::make('primary_hospital_id')
                        ->label('Hôpital principal')
                        ->relationship('primaryHospital', 'name')  // chem_hospitals.name
                        ->searchable()
                        ->preload()
                        ->columnSpan(6),

                    Select::make('academic_title_id')
                        ->label('Titre académique')
                        ->relationship('academicTitle', 'label')   // proxy_ref_academic_titles.label
                        ->searchable()
                        ->preload()
                        ->columnSpan(6),
                    Repeater::make('career_history')
                        ->label('Parcours (repeater → stocké en JSON)')
                        ->schema([
                            TextInput::make('position')->label('Poste')->maxLength(150),
                            TextInput::make('institution')->label('Institution')->maxLength(150),
                            DatePicker::make('start')->label('Début'),
                            DatePicker::make('end')->label('Fin'),
                        ])
                        ->columns(4)
                        ->collapsed()
                        ->columnSpan(12),

                        TagsInput::make('expertise_skills')
                            ->label('Compétences / Spécialisations')
                            ->placeholder('Ex: Cardiologie, Pédiatrie… (Entrée pour valider)')
                            ->suggestions([
                                'Cardiologie','Pédiatrie','Dermatologie','Gynécologie',
                                'Neurologie','Orthopédie','ORL','Ophtalmologie',
                                'Gastro-entérologie','Oncologie','Psychiatrie','Urgences',
                            ])
                            ->helperText('Ajoutez une ou plusieurs spécialités. Les données sont stockées en liste JSON.')
                            ->columnSpan(6),

                        //     Textarea::make('expertise_skills')
                        // ->label('Compétences / Spécialisations (JSON ou texte)')
                        // ->rows(3)->columnSpan(6),
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
                TextColumn::make('primaryHospital.name')->label('Hôpital')->toggleable(),
                TextColumn::make('academicTitle.label')->label('Titre académique')->toggleable(),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->label('Créé le')->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')->label('Actif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->before(function (\App\Models\ProxyDoctor $record) {
                 DB::transaction(function () use ($record) {
                    // détache les liaisons pivot
                    $record->services()->detach();

                    // si tu as d’autres relations dépendantes, supprime-les ici :
                    // $record->appointments()->delete();
                    // $record->schedules()->delete();
                    });
                }),
            ])
            ->defaultSort('id','desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('notifySms')
                    ->label('Notifier par SMS')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $sent = 0; $failed = 0;

                        foreach ($records as $doctor) {
                            $to = $doctor->user?->phone; // adapte si autre colonne
                            if ($to && Sms::send($to, "Bonjour {$doctor->fullname}, votre compte médecin a été validé chez ProxyDoc.")) {
                                $sent++;
                            } else {
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->title('Notification SMS')
                            ->body("Envoyés: {$sent}, Échecs: {$failed}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()?->hasAnyRole(['super_admin','admin']) ?? false),
                                    Tables\Actions\DeleteBulkAction::make(),
                                ]),
                            ]);
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
    public static function getRelations(): array
{
    return [
        \App\Filament\Resources\ProxyDoctorResource\RelationManagers\ServicesRelationManager::class,
    ];
}
}
