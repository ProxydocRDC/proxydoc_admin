<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\MainCity;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MainCountry;
use Illuminate\Support\Str;
use App\Models\ChemHospital;
use App\Models\ProxyService;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use App\Models\ProxyRefHospitalTier;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TimePicker;
use App\Support\Filament\RestrictToSupplier;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
class ChemHospitalResource extends Resource
{
    // use RestrictToSupplier; // la table a bien supplier_id → OK

    protected static ?string $model = ChemHospital::class;

    protected static ?string $navigationIcon   = 'heroicon-o-building-office';
    protected static ?string $navigationGroup  = 'Réseau de soins';
    protected static ?string $navigationLabel  = 'Hôpitaux';
    protected static ?string $modelLabel       = 'Hôpital';
    protected static ?string $pluralModelLabel = 'Hôpitaux';
    protected static ?int $navigationSort      = 30;

    // accès nav : admin, fournisseur, commercial
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'fournisseur', 'commercial']) ?? false;
    }

    public static function form(Form $form): Form
    {

        return $form->schema([
            Group::make([
                // ---- Identité & rattachement ----
                Section::make('Identité')->columns(12)->schema([
                    Hidden::make('created_by')
                        ->default(Auth::id()),

                    TextInput::make('name')
                        ->label("Nom de l’hôpital / clinique")
                        ->required()
                        ->maxLength(200)
                        ->reactive()
                        ->live(onBlur: true) // <-- déclenche la mise à jour seulement quand on quitte le champ
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                            // if (blank($state) || filled($get('code'))) {
                            //     return;
                            // }
                            // $set('code', self::generateHospitalCode($state));
                            $set('code', \App\Support\Code::make($state, 'HOSP'));
                        })->columnSpan(4),
                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->columnSpan(4)
                        ->maxLength(50)
                        ->helperText('Généré automatiquement depuis le nom (modifiable).')
                        ->disabled()
                        ->unique(
                            table: fn() => app(\App\Models\ChemHospital::class)->getTable(),
                            ignoreRecord: true
                        ),

                    Select::make('type')
                        ->label('Type')
                        ->options([
                            'hospital'         => 'Hôpital',
                            'clinic'           => 'Clinique',
                            'specialty_center' => 'Centre spécialisé',
                            'lab'              => 'Laboratoire',
                            'imaging'          => 'Imagerie',
                        ])->default('hospital')->columnSpan(4),

                    Select::make('ownership')
                        ->label('Propriété')
                        ->options([
                            'public'      => 'Public',
                            'private'     => 'Privé',
                            'ngo'         => 'ONG',
                            'faith_based' => 'Confessionnel',
                            'military'    => 'Militaire',
                            'university'  => 'Universitaire',
                        ])->default('private')->columnSpan(4),

                    TextInput::make('license_number')->label('Licence')->maxLength(100)->columnSpan(4),
                    TextInput::make('accreditation')->label('Accréditation')->maxLength(100)->columnSpan(4),
                ]),

                // ---- Contacts ----
                Section::make('Contacts')->columns(12)->schema([
                    TextInput::make('phone')->label('Téléphone')->maxLength(30)->columnSpan(3),
                    TextInput::make('whatsapp')->label('WhatsApp')->maxLength(30)->columnSpan(3),
                    TextInput::make('email')->label('Email')->email()->maxLength(150)->columnSpan(3),
                    TextInput::make('website')->label('Site web')->url()->maxLength(200)->columnSpan(3),
                ]),

                // ---- Adresse & géoloc ----
                Section::make('Localisation')->columns(12)->schema([
                    // PAYS
                    Select::make('country_code')
                        ->label('Pays')
                        ->options(fn() => MainCountry::orderBy('name_fr')->pluck('name_fr', 'id'))
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, ?int $state) {
                            // reset des champs dépendants
                            // $set('province', null);
                            $set('city', null);
                        })
                        ->required()
                        ->columnSpan(4),

                    // PROVINCE (active seulement si le pays a des provinces)
                    Select::make('city')
                        ->label('Ville')
                        ->options(fn(Get $get) =>
                            $get('country_code')
                                ? MainCity::where('country_id', $get('country_code'))
                                ->orderBy('city')->pluck('city', 'city')
                                : []
                        )
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->disabled(fn(Get $get) =>
                            ! $get('country_code') ||
                            ! MainCity::where('country_id', $get('country_code'))->exists()
                        )
                        ->hint(fn(Get $get) =>
                            ! $get('country_code') ? 'Sélectionnez d’abord un pays.'
                                : (MainCity::where('country_id', $get('country_code'))->exists()
                                    ? 'Provinces disponibles.' : 'Aucune province pour ce pays.')
                        )
                        ->hintColor(fn(Get $get) =>
                            ! $get('country_code') ? 'danger'
                                : (MainCity::where('country_id', $get('country_code'))->exists() ? 'success' : 'danger')
                        )
                        ->required()
                        ->columnSpan(4),

                    TextInput::make('commune')->label('Commune')->maxLength(100)->columnSpan(4),

                    TextInput::make('district')->label('Quartier / District')->maxLength(100)->columnSpan(4),
                    TextInput::make('address_line')->label('Adresse')->maxLength(255)->columnSpan(5),
                    TextInput::make('postal_code')->label('Code postal')->maxLength(12)->columnSpan(3),

                    TextInput::make('latitude')->label('Latitude')->numeric()->columnSpan(2),
                    TextInput::make('longitude')->label('Longitude')->numeric()->columnSpan(2),
                ]),

                // ---- Capacités & urgences ----
                Section::make('Capacités & Urgences')->columns(12)->schema([
                    Toggle::make('has_emergency')->label('Urgences 24/7')->columnSpan(3)
                        ->inline(false)
                        ->reactive()
                        ->helperText("Si activé, l'hôpital est considéré ouvert en continu.")
                        ->columnSpan(6),
                    Toggle::make('ambulance_available')->label('Ambulance')->columnSpan(6),
                    TextInput::make('emergency_phone')->label('Tél. urgences')->maxLength(30)->columnSpan(6),
                    TextInput::make('bed_capacity')->label('Capacité lits')->numeric()->minValue(0)->columnSpan(6),
                ]),
                Section::make('Classement / Niveau')
                    ->schema([
                        Select::make('tier_code')
                            ->label('Niveau d’hôpital')
                            ->options(
                                ProxyRefHospitalTier::query()
                                    ->where('status', 1)->orderBy('rate')
                                    ->pluck('label', 'code')
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required() // si obligatoire
                            ->rules(['exists:proxy_ref_hospital_tiers,code'])
                            ->helperText('Choisissez le niveau (référentiel).'),
                    ])->columns(2),
                // ---- Services & assurances ----
                Section::make('Services & assurances')->columns(12)->schema([

                    Select::make('departments')
                        ->label('Départements / services')
                        ->options(fn() => ProxyService::orderBy('label')->pluck('label', 'label'))
                        ->searchable()
                        ->preload()
                        ->multiple()
                        ->required()
                        ->columnSpan(6),
                    TagsInput::make('accepted_insurances')
                        ->label('Assurances acceptées')
                        ->placeholder('ex: CNSS, Mutuelle X...')
                        ->columnSpan(6),
                    Toggle::make('allow_pricing')
                        ->label("Autoriser l'affichage public de la grille tarifaire")
                        ->helperText("Si activé, votre grille (PDF/Excel/CSV) sera visible sur la plateforme.")
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, bool $state) {
                            if (! $state) {
                                // si l'hôpital retire son consentement, on efface le fichier saisi
                                $set('pricing_file', null);
                            }
                        })
                        ->columnSpan(6),

                    FileUpload::make('pricing_file')
                        ->label('Grille tarifaire')
                        ->hidden(fn(Get $get) => ! (bool) $get('allow_pricing'))
                        ->required(fn(Get $get) => (bool) $get('allow_pricing'))
                        ->disk('s3') // Filament uploade direct vers S3
                        ->directory('hospitals/pricings')
                        ->visibility('private') // retire si privé
                        ->maxSize(10240)        // 10 Mo
                        ->acceptedFileTypes([
                            'application/pdf',
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->helperText("Formats: PDF, CSV, XLS, XLSX (max 10 Mo).")
                        ->columnSpan(6),
                    Fieldset::make('opening_hours')
                        ->columns(1)
                    // ->helperText(fn(Get $get) =>
                    //     $get('has_emergency')
                    //     ? new \Illuminate\Support\HtmlString('<span class="text-green-600">Service ouvert 24/7.</span>')
                    //     : new \Illuminate\Support\HtmlString('<span class="text-red-600">Définissez les horaires par jour.</span>')
                    // )
                        ->hidden(fn(Get $get) => (bool) $get('has_emergency'))
                        ->schema([
                                                                   // Un repeater d'une ligne par jour
                            Repeater::make('opening_hours_editor') // ← champ "éditeur" non persisté
                                ->dehydrated(false)
                                ->columns(12)
                                ->default(function () {
                                    // Préremplir les 7 jours fermés
                                    $days = [
                                        ['day' => 1, 'label' => 'Lundi'],
                                        ['day' => 2, 'label' => 'Mardi'],
                                        ['day' => 3, 'label' => 'Mercredi'],
                                        ['day' => 4, 'label' => 'Jeudi'],
                                        ['day' => 5, 'label' => 'Vendredi'],
                                        ['day' => 6, 'label' => 'Samedi'],
                                        ['day' => 7, 'label' => 'Dimanche'],
                                    ];
                                    return collect($days)->map(fn($d) => [
                                        'day'   => $d['day'],
                                        'label' => $d['label'],
                                        'open'  => false,
                                        'slots' => [], // ex: [['start' => '08:00','end'=>'12:00']]
                                    ])->all();
                                })
                                ->itemLabel(fn(array $state) => ($state['label'] ?? 'Jour'))
                                ->addable(false) // on fige les 7 jours
                                ->deletable(false)
                                ->reorderable(false)
                                ->schema([
                                    Select::make('label')
                                        ->label('Jour')
                                        ->options([
                                            'Lundi' => 'Lundi', 'Mardi'    => 'Mardi', 'Mercredi'  => 'Mercredi',
                                            'Jeudi' => 'Jeudi', 'Vendredi' => 'Vendredi', 'Samedi' => 'Samedi', 'Dimanche' => 'Dimanche',
                                        ])
                                        ->disabled()
                                        ->columnSpan(3),

                                    Toggle::make('open')
                                        ->label('Ouvert ?')
                                        ->reactive()
                                        ->columnSpan(2),

                                    // Plages horaires (on montre seulement si "ouvert")
                                    Repeater::make('slots')
                                        ->label('Plages')
                                        ->hidden(fn(Get $get) => ! (bool) $get('open'))
                                        ->minItems(1)
                                        ->columns(6)
                                        ->schema([
                                            TimePicker::make('start')
                                                ->label('Début')
                                                ->seconds(false)
                                                ->required()
                                                ->columnSpan(3),
                                            TimePicker::make('end')
                                                ->label('Fin')
                                                ->seconds(false)
                                                ->required()
                                                ->columnSpan(3),
                                        ])
                                        ->columnSpan(7),
                                ]),

                        ])
                        ->columnSpan(12),
                ]),

                // ---- Médias & présentation ----
                Section::make('Médias & présentation')->columns(12)->schema([
                    FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->directory('hospitals/logo')
                        ->disk('s3')
                        ->visibility('private')
                        ->columnSpan(4),

                    FileUpload::make('images')
                        ->label('Photos')
                        ->multiple()
                        ->directory('hospitals/images')
                        ->disk('s3')
                        ->visibility('private')
                        ->columnSpan(8),

                    Textarea::make('description')
                        ->label('Description / notes agent')
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpan(12),
                        Toggle::make('status')
                                        ->label('Visible')
                                        ->reactive()
                                        ->columnSpan(6),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->getStateUsing(fn($record) => $record->mediaUrl('logo'))
                    ->defaultImageUrl(asset('assets/images/default.jpg')) // 👈 évite l’icône cassée
                    ->size(64)
                    ->square()
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->mediaUrl('logo', ttl: 5))
                    ->openUrlInNewTab(),
                ImageColumn::make('images')
                    ->label('Images')
                // renvoie un ARRAY d’URLs pour l’affichage empilé
                    // ->getStateUsing(fn($record) => $record->mediaUrls('images'))
                     ->getStateUsing(fn($record) => is_array($record?->mediaUrls('images')) ? $record->mediaUrls('images') : [])
                    ->defaultImageUrl(asset('assets/images/default.jpg'))
                    ->circular()
                    ->stacked()
                    ->limit(2)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limitedRemainingText()
                    ->height(44), // ou ->size(44)
                TextColumn::make('code')->label('Code')->searchable()->toggleable(),
                TextColumn::make('name')->label('Hôpital')->searchable()->sortable(),
                TextColumn::make('city')->label('Ville')->toggleable(),
                TextColumn::make('province')->label('Province')->toggleable(),
                TextColumn::make('phone')->label('Tél.')->toggleable(),
                // Type
                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => ['hospital', 'clinic', 'specialty_center', 'lab', 'imaging'],
                    ])
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'hospital'         => 'Hôpital',
                        'clinic'           => 'Clinique',
                        'specialty_center' => 'Centre spé.',
                        'lab'              => 'Laboratoire',
                        'imaging'          => 'Imagerie',
                        default            => $state,
                    }),
                TextColumn::make('tier.label')
                    ->label('Niveau')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tier.rate')
                    ->label('Taux')
                    ->formatStateUsing(fn($state) => $state === null ? '—' : number_format((float) $state, 2, ',', ' ') . ' %')
                    ->alignRight()
                    ->sortable(),
                IconColumn::make('has_emergency')->label('Urgences')->boolean(),
                BadgeColumn::make('allow_pricing')
                    ->label('Tarifs publiés')
                    ->formatStateUsing(fn($state) => (bool) $state ? 'Oui' : 'Non')
                // 2 façons : soit colors([...]) avec closures, soit une seule closure color()
                // A) mapping de couleurs
                    ->colors([
                        'success' => fn($state): bool => (bool) $state,
                        'danger'  => fn($state): bool  => ! (bool) $state,
                    ]),
                IconColumn::make('allow_pricing')->label('Urgences')->boolean(),

                TextColumn::make('pricing_file')
                    ->label('Fichier')
                    ->getStateUsing(fn($record) => $record->pricing_file ? 'Voir' : '—')
                    ->url(fn($record) => $record->pricing_file
                            ? Storage::disk(config('filesystems.default', 'public'))->url($record->pricing_file)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Type')->options([
                    'hospital' => 'Hôpital', 'clinic'      => 'Clinique', 'specialty_center' => 'Centre spé.',
                    'lab'      => 'Laboratoire', 'imaging' => 'Imagerie',
                ]),
                Tables\Filters\SelectFilter::make('ownership')->label('Propriété')->options([
                    'public'      => 'Public', 'private'         => 'Privé', 'ngo'            => 'ONG',
                    'faith_based' => 'Confessionnel', 'military' => 'Militaire', 'university' => 'Universitaire',
                ]),
                Tables\Filters\SelectFilter::make('status')->label('Actif ?')->options([1 => 'Actif', 0 => 'Inactif']),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('clearImages')
                        ->label('Vider images')
                        ->icon('heroicon-m-photo')
                        ->color('warning')
                        ->visible(fn($record) => ! empty($record->images))
                        ->form([
                            \Filament\Forms\Components\Toggle::make('delete_s3')
                                ->label('Supprimer aussi les fichiers S3')
                                ->helperText('Sinon, seules les références en base seront vidées.')
                                ->default(false),
                        ])
                        ->requiresConfirmation()
                        ->action(function (array $data, $record) {
                            $keys    = is_array($record->logo) ? $record->logo : null;
                            $deleted = 0;

                            if (! empty($data['delete_s3']) && $keys) {
                                $disk = Storage::disk('s3');
                                foreach ($keys as $k) {
                                    // au cas où il resterait une URL complète :
                                    $key = preg_match('#^https?://#i', (string) $k)
                                        ? ltrim(parse_url($k, PHP_URL_PATH) ?? '', '/')
                                        : ltrim((string) $k, '/');

                                    $bucket = config('filesystems.disks.s3.bucket');
                                    if ($bucket && Str::startsWith($key, $bucket . '/')) {
                                        $key = substr($key, strlen($bucket) + 1);
                                    }

                                    try {
                                        if ($key) {
                                            $disk->delete($key);
                                            $deleted++;
                                        }
                                    } catch (\Throwable $e) {
                                        // on continue, on notifie juste à la fin
                                    }
                                }
                            }

                            // Vider la colonne en base (choix: [] plutôt que null)
                            $record->logo = null;
                            $record->save();

                            Notification::make()
                                ->title('Images vidées')
                                ->body(($deleted ? "Fichiers S3 supprimés: {$deleted}. " : '') . 'La colonne "images" est maintenant vide.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\ViewAction::make()->label('Voir'),
                    Tables\Actions\EditAction::make()->label('Modifier'),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn() => Auth::user()?->hasRole('super_admin'))
                        ->label('Supprimer'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn() => Auth::user()?->hasRole('super_admin')),
            ])->emptyStateHeading('Aucun hôpital visible')
->emptyStateDescription('Vérifiez vos filtres, vos droits ou le fournisseur actif.');
    }
public static function getEloquentQuery(): Builder
{
    $u = Auth::user();

    $q = parent::getEloquentQuery()
        ->with(['tier']); // pour TextColumn::make('tier.label')

    // multi-tenant / visibilité (exemple)
    if (! $u?->hasRole('super_admin')) {
        $supplierId = $u?->supplier?->id ?? 0;
        $q->where('supplier_id', $supplierId);
    }

    // si tu as un statut actif/inactif et que tu veux ne lister que les actifs :
    // $q->where('status', 1);

    // si le modèle utilise SoftDeletes et que tu ne veux PAS l’état "corbeille" par défaut,
    // NE change rien (Filament exclut déjà deleted_at != null via SoftDeletingScope)
    // Sinon, pour afficher aussi la corbeille par défaut :
    // $q->withoutGlobalScopes([SoftDeletingScope::class]);

    return $q;
}
    // Enregistrement automatique des métadonnées
    // public static function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['created_by'] = $data['created_by'] ?? Auth::id();
    //     $data['updated_by'] = $data['updated_by'] ?? Auth::id();
    //     // si agent/fournisseur connecté → impose son supplier_id
    //     if (Auth::user()?->hasRole('fournisseur')) {
    //         $data['supplier_id'] = Auth::user()->supplier?->id;
    //     }
    //     return $data;
    // }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        $data['serialize']  = static::serializeOpeningHours($data);
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\ChemHospitalResource\Pages\ListChemHospitals::route('/'),
            'create' => \App\Filament\Resources\ChemHospitalResource\Pages\CreateChemHospital::route('/create'),
            'edit'   => \App\Filament\Resources\ChemHospitalResource\Pages\EditChemHospital::route('/{record}/edit'),
            'view'   => \App\Filament\Resources\ChemHospitalResource\Pages\ViewChemHospital::route('/{record}'),
        ];
    }
    protected static function generateHospitalCode(?string $name): string
    {
        $initials = collect(preg_split('/[\s\-]+/u', Str::of((string) $name)->squish()))
            ->filter()
            ->reject(fn($w) => in_array(mb_strtolower($w), ['de', 'du', 'des', 'la', 'le', 'les', 'l\'', 'd\'', 'et', 'en', 'à', 'au', 'aux', 'pour', 'sur', 'sous', 'chez'], true))
            ->map(fn($w) => Str::upper(Str::substr(Str::of($w)->replace(['l\'', 'd\''], ''), 0, 1)))
            ->implode('') ?: Str::upper(Str::substr(Str::slug((string) $name), 0, 3));

        return "PROXY-{$initials}-" . random_int(10000, 99999);
    }
    // Pour l’édition : pré-remplir l’éditeur depuis opening_hours stocké
    public static function mutateFormDataBeforeFill(array $data): array
    {
        $oh                           = $data['opening_hours'] ?? [];
        $data['has_emergency']        = (bool) ($oh['24_7'] ?? false);
        $data['opening_hours_editor'] = static::openingHoursToEditor($oh);
        return $data;
    }
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Remplissage auto si vide
        if (blank($data['code'] ?? null)) {
            $data['code'] = static::generateHospitalCode($data['name'] ?? null);
        }

        // Boucle d’unicité (très rapide)
        while (\App\Models\ChemHospital::where('code', $data['code'])->exists()) {
            $data['code'] = static::generateHospitalCode($data['name'] ?? null);
        }
        $data['serialize'] = static::serializeOpeningHours($data);
        // … tes autres métadonnées
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['updated_by'] = $data['updated_by'] ?? Auth::id();

        return $data;
    }
// À l’update

// ---- Helpers ----
    protected static function serializeOpeningHours(array $data): array
    {
        $map = ['24_7' => false];

        if (! empty($data['has_emergency'])) {
            $map['24_7'] = true;
        } elseif (! empty($data['opening_hours_editor']) && is_array($data['opening_hours_editor'])) {
            foreach ($data['opening_hours_editor'] as $row) {
                if (! ($row['open'] ?? false)) {
                    continue;
                }

                $day = (string) ($row['day'] ?? '');
                if ($day === '') {
                    continue;
                }

                $slots = [];
                foreach (($row['slots'] ?? []) as $slot) {
                    $start = $slot['start'] ?? null;
                    $end   = $slot['end'] ?? null;
                    if ($start && $end) {
                        $slots[] = [substr($start, 0, 5), substr($end, 0, 5)];
                    }
                }
                if ($slots) {
                    $map[$day] = $slots;
                }

            }
        }

        $data['opening_hours'] = $map;
        unset($data['opening_hours_editor'], $data['has_emergency']); // 🔑 on retire les champs non persistés
        return $data;
    }

    protected static function openingHoursToEditor(array $oh): array
    {
        $days = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
        $out  = [];
        foreach ($days as $d => $label) {
            $slots = [];
            if (! empty($oh[(string) $d]) && is_array($oh[(string) $d])) {
                foreach ($oh[(string) $d] as $pair) {
                    $slots[] = ['start' => $pair[0] ?? null, 'end' => $pair[1] ?? null];
                }
            }
            $out[] = ['day' => $d, 'label' => $label, 'open' => ! empty($slots), 'slots' => $slots];
        }
        return $out;
    }
     public static function getNavigationBadge(): ?string
    {
        // total global de lignes “produit en pharmacie”

        $base = ChemHospital::query();

        return (string) $base->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger'; // ou 'success', 'warning', etc.
    }
}
