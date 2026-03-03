<?php
namespace App\Filament\Resources;

use App\Filament\Actions\TrashAction;
use App\Filament\Actions\TrashBulkAction;
use App\Filament\Concerns\HasTrashableRecords;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\SelectColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model           = User::class;
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $navigationLabel = 'Utilisateurs';
    protected static ?string $navigationIcon  = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Identité')
                            ->schema([
                                TextInput::make('firstname')
                                    ->label('Prénom')
                                    ->maxLength(50)
                                    ->columnSpan(4),
                                TextInput::make('lastname')
                                    ->label('Nom')
                                    ->maxLength(50)
                                    ->columnSpan(4),
                                TextInput::make('username')
                                    ->label('Nom d\'utilisateur')
                                    ->maxLength(100)
                                    ->unique(
                                        table: User::class,
                                        column: 'username',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn(Unique $rule) => $rule->whereNull('deleted_at')
                                    )
                                    ->columnSpan(4),
                                DatePicker::make('birth_date')
                                    ->label('Date de naissance')
                                    ->native(false)
                                    ->columnSpan(4),
                                Select::make('gender')
                                    ->label('Genre')
                                    ->options(['M' => 'Masculin', 'F' => 'Féminin'])
                                    ->columnSpan(4),
                                FileUpload::make('profile')
                                    ->label('Photo de profil')
                                    ->directory('uploads/images/profiles')
                                    ->disk('s3')
                                    ->visibility('private')
                                    ->columnSpan(4),
                            ])->columns(12),
                        Tabs\Tab::make('Contact')
                            ->schema([
                                Select::make('phone_country_code')
                                    ->label('Indicatif pays')
                                    ->options([
                                        '243' => '+243 (RDC)',
                                        '33'  => '+33 (France)',
                                        '32'  => '+32 (Belgique)',
                                        '237' => '+237 (Cameroun)',
                                        '242' => '+242 (Congo)',
                                        '250' => '+250 (Rwanda)',
                                        '256' => '+256 (Ouganda)',
                                        '254' => '+254 (Kenya)',
                                    ])
                                    ->default('243')
                                    ->dehydrated(false)
                                    ->columnSpan(2),
                                TextInput::make('phone')
                                    ->label('Numéro')
                                    ->tel()
                                    ->required()
                                    ->dehydrateStateUsing(function ($state, $livewire) {
                                        $code = $livewire->data['phone_country_code'] ?? '243';
                                        $num  = preg_replace('/\D/', '', trim($state ?? ''));
                                        return $num ? '+' . $code . $num : null;
                                    })
                                    ->unique(
                                        table: User::class,
                                        column: 'phone',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at')
                                    )
                                    ->validationMessages([
                                        'unique' => 'Ce numéro de téléphone est déjà utilisé.',
                                    ])
                                    ->live(onBlur: true)
                                    ->placeholder('8XX XXX XXXX')
                                    ->helperText('Sans indicatif, le préfixe sélectionné sera ajouté.')
                                    ->columnSpan(4)
                                    ->maxLength(20),
                        TextInput::make('email')
                            ->email()->columnSpan(6)
                            ->maxLength(250)
                        // normalise l'email avant sauvegarde (évite les doublons "Case" différents)
                            ->dehydrateStateUsing(fn($state) => strtolower(trim($state)))
                        // validation unique (ignore l’enregistrement en cours et exclut les soft-deleted)
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignoreRecord: true,
                                modifyRuleUsing: fn(Unique $rule) => $rule->whereNull('deleted_at')
                            )
                            ->validationMessages([
                                'unique' => 'Cet email est déjà utilisé.',
                            ])
                            ->columnSpan(6),
                            ])->columns(12),
                        Tabs\Tab::make('Compte')
                            ->schema([
                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->rule(PasswordRule::defaults()) // complexité (Laravel)
                            ->required(fn(string $context) => $context === 'create')
                            ->same('password_confirmation') // DOIT correspondre au champ ci-dessous
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state, ['rounds' => 10]) : null)
                            ->dehydrated(fn($state) => filled($state)) // n’écrase pas si vide en édition
                            ->columnSpan(6)
                            ->required(fn(string $context): bool => $context === 'create')
                            ->visible(fn($livewire) => $livewire instanceof CreateRecord || $livewire instanceof EditRecord),

                        // Confirmation (non enregistrée en base)
                        TextInput::make('password_confirmation')
                            ->label('Confirmer le mot de passe')
                            ->password()
                            ->revealable()
                            ->dehydrated(false) // ne pas sauvegarder en DB
                            ->required(fn(string $context) => $context === 'create')
                            ->columnSpan(6),
                        Select::make('default_role')
                            ->default(1) // Activé
                            ->dehydrated()
                            ->visible(fn($livewire) => $livewire instanceof CreateRecord || $livewire instanceof EditRecord)
                            ->options([
                                1 => 'Activé',
                                2 => 'Docteur',
                                3 => 'Livreur',
                                4 => 'Supprimé',
                                5 => 'Patient',
                            ])
                            ->columnSpan(6),
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (?array $state, Set $set) {
                                // Premier rôle choisi = rôle par défaut
                                $set('default_role', $state[0] ?? null);
                            })
                            ->columnSpan(6),
                        Select::make('status')
                            ->label('Statut compte')
                            ->default(1)
                            ->options([
                                0 => 'Supprimé',
                                1 => 'Activé',
                                2 => 'En attente',
                                3 => 'Désactivé',
                                4 => 'Validé, attente infos',
                                5 => 'En cours (OTP à valider)',
                            ])
                            ->columnSpan(6),
                        Hidden::make('account_id')
                            ->default(null)
                            ->dehydrated(true),
                        Placeholder::make('created_by_info')
                            ->label('Créé par')
                            ->content('Sera défini automatiquement (utilisateur connecté)')
                            ->visible(fn ($livewire) => $livewire instanceof CreateRecord)
                            ->columnSpan(4),
                        Placeholder::make('otp_info')
                            ->label('OTP')
                            ->content('Généré automatiquement (unique)')
                            ->visible(fn ($livewire) => $livewire instanceof CreateRecord)
                            ->columnSpan(4),
                        Placeholder::make('otp_display')
                            ->label('OTP actuel')
                            ->content(fn ($record) => $record?->otp ?? '—')
                            ->visible(fn ($livewire) => $livewire instanceof EditRecord)
                            ->columnSpan(4),
                        TextInput::make('ip_address')
                            ->label('Adresse IP')
                            ->maxLength(100)
                            ->columnSpan(4),
                        TextInput::make('fcm_token')
                            ->label('FCM Token')
                            ->maxLength(1000)
                            ->columnSpan(4),
                        TextInput::make('public_token')
                            ->label('Token public')
                            ->maxLength(1000)
                            ->columnSpan(4),
                    ])->columns(12),
                        Tabs\Tab::make('Patient')
                            ->schema([
                                Toggle::make('activate_as_patient')
                                    ->label('Activer en tant que patient')
                                    ->helperText('Si activé, les informations patient sont obligatoires.')
                                    ->live()
                                    ->columnSpanFull(),
                                TextInput::make('patient_fullname')
                                    ->label('Nom complet')
                                    ->maxLength(200)
                                    ->required(fn (Get $get) => (bool) $get('activate_as_patient'))
                                    ->columnSpan(6),
                                DatePicker::make('patient_birthdate')
                                    ->label('Date de naissance')
                                    ->native(false)
                                    ->required(fn (Get $get) => (bool) $get('activate_as_patient'))
                                    ->columnSpan(6),
                                Select::make('patient_gender')
                                    ->label('Genre')
                                    ->options(['male' => 'Homme', 'female' => 'Femme', 'other' => 'Autre'])
                                    ->required(fn (Get $get) => (bool) $get('activate_as_patient'))
                                    ->columnSpan(6),
                                Select::make('patient_blood_group')
                                    ->label('Groupe sanguin')
                                    ->options([
                                        'A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-',
                                        'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-',
                                    ])
                                    ->columnSpan(6),
                                Select::make('patient_relation')
                                    ->label('Relation')
                                    ->options([
                                        'self' => 'Moi-même', 'child' => 'Enfant', 'parent' => 'Parent',
                                        'spouse' => 'Conjoint', 'sibling' => 'Frère/soeur', 'friend' => 'Ami', 'other' => 'Autre',
                                    ])
                                    ->default('self')
                                    ->required(fn (Get $get) => (bool) $get('activate_as_patient'))
                                    ->columnSpan(6),
                                TextInput::make('patient_phone')
                                    ->label('Téléphone patient')
                                    ->tel()
                                    ->maxLength(20)
                                    ->columnSpan(6),
                                TextInput::make('patient_email')
                                    ->label('Email patient')
                                    ->email()
                                    ->maxLength(150)
                                    ->columnSpan(6),
                                KeyValue::make('patient_allergies')
                                    ->label('Allergies')
                                    ->reorderable()
                                    ->columnSpan(6),
                                KeyValue::make('patient_chronic_conditions')
                                    ->label('Maladies chroniques')
                                    ->reorderable()
                                    ->columnSpan(6),
                            ])->columns(12)
                            ->visible(fn ($livewire) => $livewire instanceof CreateRecord || $livewire instanceof EditRecord || $livewire instanceof \Filament\Resources\Pages\ViewRecord),
                ])->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
         $roleOptions = [
        1 => 'Activé',
        2 => 'Docteur',
        3 => 'Livreur',
        4 => 'Supprimé',
    ];
        return $table
            ->columns([
                // TextColumn::make('profile')
                //     ->label('Profil')
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->searchable(),
                ImageColumn::make('profile')
                    ->label('Profil')
                    ->getStateUsing(fn($record) => $record->profile_url) // ← l’URL signée
                    ->defaultImageUrl(asset('assets/images/default.jpg'))
                    ->circular()
                    ->height(44),
                TextColumn::make('firstname')
                    ->label('Prénom')
                    ->searchable(),
                TextColumn::make('lastname')
                    ->label('Nom')
                    ->searchable(),
                SelectColumn::make('status')
                    ->label('Statut')
                    ->options([
                        '0' => 'Supprimé',
                        '1' => 'Activé',
                        '2' => 'En attente',
                        '3' => 'Désactivé',
                        '4' => 'Validé, attente infos',
                        '5' => 'En cours (OTP à valider)',
                    ])->disabled(fn() => Auth::user()?->hasRole('viewer')) // 🔒 désactive si rôle = viewer
                    ->afterStateUpdated(function ($record, $state) {
                        $statusText = match ($state) {
                            '0'     => 'Supprimé',
                            '1'     => 'Activé',
                            '2'     => 'En attente',
                            '3'     => 'Désactivé',
                            '4'     => 'Validé, attente infos',
                            '5'     => 'En cours (OTP à valider)',
                            default => 'Inconnu',
                        };

                        Notification::make()
                            ->title("Statut mis à jour")
                            ->body("L'utilisateur {$record->firstname} est maintenant **{$statusText}**.")
                            ->success() // ou ->danger(), ->info(), etc.
                            ->send();
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('processus_termine')
                    ->label('Processus terminé')
                    ->badge()
                    ->getStateUsing(fn (User $record) => $record->hasCompletedRegistration() ? 'Oui' : 'Non')
                    ->color(fn (User $record) => $record->hasCompletedRegistration() ? 'success' : 'warning')
                    ->toggleable(),
                TextColumn::make('roles')
                    ->label('Rôles')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->getRoleNames(); // Spatie: retourne une collection des noms de rôle
                    })
                    ->colors([
                        'primary' => 'super_admin',
                        'success' => 'Pharmacie',
                        'warning' => 'utilisateur',
                        'gray'    => 'invité',
                    ]),
                    TextColumn::make('default_role')
    ->label('Rôle par défaut')
    ->formatStateUsing(fn ($state) => [1=>'Activé',2=>'Docteur',3=>'Livreur',4=>'Supprimé'][$state] ?? '—')
    ->badge()
    ->colors([
        'success' => fn ($state) => (int)$state === 1,
        'info'    => fn ($state) => (int)$state === 2,
        'warning' => fn ($state) => (int)$state === 3,
        'danger'  => fn ($state) => (int)$state === 4,
    ])
    ->sortable(),
                TextColumn::make('username')
                    ->label('Nom d’utilisateur')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('account_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->disabled()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('last_activity')
                    ->label('Dernière activité')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('otp')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('country')
                    ->label('Pays')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Ville')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_by')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])->filters([
        //    SelectFilter::make('role')
        // ->label('Rôle')
        // ->options([1=>'Activé',2=>'Docteur',3=>'Livreur',4=>'Supprimé'])
        // ->searchable()->preload(),
        ...array_filter([static::getTrashFilter()]),
        SelectFilter::make('default_role')
            ->label('Rôle')
            ->options([1=>'Activé',2=>'Docteur',3=>'Livreur',4=>'Supprimé'])
            ->searchable()->preload(),
        Filter::make('created_at')
            ->label('Date d\'inscription')
            ->form([
                DatePicker::make('created_from')->label('Du')->native(false),
                DatePicker::make('created_to')->label('Au')->native(false),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['created_from'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from']))
                    ->when($data['created_to'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_to']));
            }),
        Filter::make('processus')
            ->label('Processus')
            ->form([
                \Filament\Forms\Components\Select::make('value')
                    ->label('Processus')
                    ->options([
                        'termine'  => 'Terminé (tél. vérifié par OTP)',
                        'en_cours' => 'En cours (OTP à valider)',
                    ])
                    ->placeholder('— Tous —'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return match ($data['value'] ?? null) {
                    'termine'  => $query->where('status', '!=', 5),
                    'en_cours' => $query->where('status', 5),
                    default    => $query,
                };
            }),
        SelectFilter::make('status')
            ->label('Statut compte')
            ->options([
                0 => 'Supprimé',
                1 => 'Activé',
                2 => 'En attente',
                3 => 'Désactivé',
                4 => 'Validé, attente infos',
                5 => 'En cours (OTP à valider)',
            ]),
        ])
            ->persistFiltersInSession()
            ->filtersFormColumns(1)
            ->actions([
                ActionGroup::make([
                    Action::make('clearProfile')
                        ->label('Supprimer la photo')
                        ->icon('heroicon-m-user-minus')
                        ->color('warning')
                        ->visible(fn($record) => filled($record->profile)) // une seule image
                        ->form([
                            \Filament\Forms\Components\Toggle::make('delete_s3')
                                ->label('Supprimer aussi le fichier S3')
                                ->helperText('Sinon, seule la référence en base sera vidée.')
                                ->default(false),
                        ])
                        ->requiresConfirmation()
                        ->action(function (array $data, $record) {
                            $deleted = 0;

                            // Récupère la clé/chemin S3 stocké en base (string)
                            $key = (string) $record->profile;

                            if (! empty($data['delete_s3']) && $key) {
                                $disk = Storage::disk('s3');

                                // Si on a reçu une URL complète, on en extrait le path
                                if (preg_match('#^https?://#i', $key)) {
                                    $key = ltrim(parse_url($key, PHP_URL_PATH) ?? '', '/');
                                } else {
                                    $key = ltrim($key, '/');
                                }

                                // Retire le préfixe bucket/… si présent
                                $bucket = config('filesystems.disks.s3.bucket');
                                if ($bucket && Str::startsWith($key, $bucket . '/')) {
                                    $key = substr($key, strlen($bucket) + 1);
                                }

                                try {
                                    if ($key) {
                                        $disk->delete($key);
                                        $deleted = 1;
                                    }
                                } catch (\Throwable $e) {
                                    // on ignore l’erreur S3 ici, on continue à vider la base
                                }
                            }

                                                     // Vider la colonne en base (NULL ou '' selon ta préférence)
                            $record->profile = null; // ou '' si tu préfères
                            $record->save();

                            Notification::make()
                                ->title('Photo de profil supprimée')
                                ->body(($deleted ? "Fichier S3 supprimé.\n" : '') . 'Le champ "profile" a été vidé.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    TrashAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('clearProfileBulk')
                        ->label('Supprimer photos de profil (en masse)')
                        ->icon('heroicon-m-user-minus')
                        ->color('warning')
                        ->deselectRecordsAfterCompletion()
                        ->form([
                            \Filament\Forms\Components\Toggle::make('delete_s3')
                                ->label('Supprimer aussi les fichiers S3')
                                ->helperText('Sinon, seules les références en base seront vidées.')
                                ->default(false),
                        ])
                        ->action(function (array $data, $records) {
                            $totalRecords      = 0;
                            $totalFilesDeleted = 0;

                            $deleteOnS3 = ! empty($data['delete_s3']);
                            $disk       = Storage::disk('s3');

                            foreach ($records as $record) {
                                $totalRecords++;

                                // Récupère la clé/URL stockée (string)
                                $key = (string) ($record->profile ?? '');

                                if ($deleteOnS3 && $key !== '') {
                                    // si on a une URL complète, extraire le path
                                    if (preg_match('#^https?://#i', $key)) {
                                        $key = ltrim(parse_url($key, PHP_URL_PATH) ?? '', '/');
                                    } else {
                                        $key = ltrim($key, '/');
                                    }

                                    // retirer le préfixe "<bucket>/" si présent
                                    $bucket = config('filesystems.disks.s3.bucket');
                                    if ($bucket && Str::startsWith($key, $bucket . '/')) {
                                        $key = substr($key, strlen($bucket) + 1);
                                    }

                                    try {
                                        if ($key) {
                                            $disk->delete($key);
                                            $totalFilesDeleted++;
                                        }
                                    } catch (\Throwable $e) {
                                        // on ignore et on continue
                                    }
                                }

                                                         // Vider la colonne en base
                                $record->profile = null; // ou '' si tu préfères
                                $record->save();
                            }

                            Notification::make()
                                ->title('Photos de profil supprimées')
                                ->body("Enregistrements traités : {$totalRecords}. Fichiers S3 supprimés : {$totalFilesDeleted}.")
                                ->success()
                                ->send();
                        }),
                    TrashBulkAction::make(),
                ]),
            ]);
    }
    public static ?array $pendingPatientData = null;

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['default_role'] = User::ROLE_PATIENT; // 5

        // Extraire les données patient (ne pas les envoyer au modèle User)
        $patientKeys = array_filter(array_keys($data), fn ($k) => str_starts_with((string) $k, 'patient_') || $k === 'activate_as_patient');
        if (! empty($patientKeys)) {
            static::$pendingPatientData = array_intersect_key($data, array_flip($patientKeys));
            $data = array_diff_key($data, array_flip($patientKeys));
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $patientKeys = array_filter(array_keys($data), fn ($k) => str_starts_with((string) $k, 'patient_') || $k === 'activate_as_patient');
        if (! empty($patientKeys)) {
            static::$pendingPatientData = array_intersect_key($data, array_flip($patientKeys));
            return array_diff_key($data, array_flip($patientKeys));
        }
        return $data;
    }
    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\UserResource\RelationManagers\PatientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view'   => Pages\ViewUser::route('/{record}'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {

        $base = User::query();

        return (string) $base->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger'; // ou 'success', 'warning', etc.
    }
}
