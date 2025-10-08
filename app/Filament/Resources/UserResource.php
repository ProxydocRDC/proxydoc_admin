<?php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\Rules\Unique;

class UserResource extends Resource
{
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
                            ->multiple()
                            ->directory('hospitals/profile')
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
        return $table
            ->columns([
                // TextColumn::make('profile')
                //     ->label('Profil')
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->searchable(),
                ImageColumn::make('Profile')
                    ->label('Profile')
                // renvoie un ARRAY d’URLs pour l’affichage empilé
                    ->getStateUsing(fn($record) => $record->mediaUrls('profile'))
                    ->defaultImageUrl(asset('images/PROFI-TIK.jpg'))
                    ->circular()
                    ->stacked()
                    ->limit(2)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limitedRemainingText()
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
                        '1' => 'Activé',
                        '2' => 'En attente',
                        '3' => 'Désactivé',
                        '4' => 'Supprimé',
                    ])->disabled(fn() => Auth::user()?->hasRole('viewer')) // 🔒 désactive si rôle = viewer
                    ->afterStateUpdated(function ($record, $state) {
                        $statusText = match ($state) {
                            '1'     => 'Activé',
                            '2'     => 'En attente',
                            '3'     => 'Désactivé',
                            '4'     => 'Supprimé',
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
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
