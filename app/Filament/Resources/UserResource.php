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
                Group::make([
                    Section::make("Formulaire d'actualité")->schema([
                        TextInput::make('firstname')
                            ->label('prénom')
                            ->required()
                            ->columnSpan(6),
                        TextInput::make('lastname')
                            ->label('Nom')
                            ->required()
                            ->columnSpan(6),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                        // normalise le numéro (ex. +243..., enlève espaces)
                            ->dehydrateStateUsing(fn($state) => preg_replace('/\s+/', '', trim($state)))
                            ->unique(
                                table: User::class,
                                column: 'phone',
                                ignoreRecord: true,
                                modifyRuleUsing: fn(Unique $rule) => $rule->whereNull('deleted_at')
                            )
                            ->validationMessages([
                                'unique' => 'Ce numéro de téléphone est déjà utilisé.',
                            ])
                            ->live(onBlur: true)
                        // ->tel()
                            ->required()->columnSpan(6)
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
                            ->live(onBlur: true),

                        // Mot de passe
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
                                '1' => 'Activé',
                                '2' => 'Docteur',
                                '3' => 'Livreur',
                                '4' => 'Supprimé',
                            ])->columnSpan(6),
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
                        FileUpload::make('profile')
                            ->label('Photo de profil')
                            ->directory('uploads/images/profiles')
                            ->disk('s3')
                            ->visibility('private')
                            ->columnSpan(12),
// Champ caché pour stocker l'ID du rôle par défaut
                        Hidden::make('default_role')
                            ->dehydrated(true) // envoyer en base
                            ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                // À l’ouverture du form : si vide, initialise depuis "roles"
                                if (blank($state)) {
                                    $roles = $get('roles') ?? [];
                                    $set('default_role', $roles[0] ?? null);
                                }
                            }),
                        TextInput::make('account_id')
                            ->default(1)
                            ->numeric()->hidden(),
                        Hidden::make('status')->default(1),
                    ])->columnS(12),
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
                TextColumn::make('root_store')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

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
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['default_role'] = User::ROLE_PATIENT; // 5
        return $data;
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
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
