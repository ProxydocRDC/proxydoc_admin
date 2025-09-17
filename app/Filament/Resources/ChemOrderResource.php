<?php
namespace App\Filament\Resources;

use App\Models\User;
use App\Support\Sms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\ChemOrder;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\ChemShipment;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ChemOrderResource\Pages;

class ChemOrderResource extends Resource
{
    // use RestrictToSupplier;
    protected static ?string $model = ChemOrder::class;

    /** Menu & libellés */
    protected static ?string $navigationIcon   = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup  = 'Ventes';
    protected static ?string $navigationLabel  = 'Commandes';
    protected static ?string $modelLabel       = 'Commande';
    protected static ?string $pluralModelLabel = 'Commandes';

    protected static function supplierOwnerPath(): string
    {
        return 'pharmacy.supplier_id';
    }

    // ownerColumn() reste 'created_by'

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        return $data;
    }
    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make([
                Section::make('Métadonnées')->schema([
                    Hidden::make('created_by')->default(Auth::id()),

                    Select::make('status')
                        ->label('Statut (technique)')
                        ->options([1 => 'Actif', 0 => 'Inactif'])
                        ->default(1)
                        ->helperText("Statut interne d'activation de l'enregistrement."),

                    Select::make('order_status')
                        ->label('Statut commande')
                        ->options([
                            'created'  => 'Créée',
                            'pending'  => 'En attente',
                            'paid'     => 'Payée',
                            'canceled' => 'Annulée',
                            'accepted' => 'Acceptée',
                        ])
                        ->required()
                        ->helperText('État commercial de la commande.'),
                ])->columns(3),

                Section::make('Parties & références')->schema([
                    Select::make('customer_id')
                        ->label('Client')
                        ->relationship('customer', 'firstname') // adapte si ton User a un autre champ
                        ->searchable()->preload()->required()
                        ->helperText('Utilisateur qui passe la commande.'),

                    Select::make('pharmacy_id')
                        ->label('Pharmacie (optionnel)')
                        ->relationship('pharmacy', 'name')
                        ->searchable()->preload()
                        ->helperText('Pharmacie liée à la commande, si applicable.'),
                ])->columns(2),

                Section::make('Montants')->schema([
                    TextInput::make('amount')
                        ->label('Montant produits')
                        ->numeric()->minValue(0)->step('0.01')->required()
                        ->helperText('Somme des lignes de produits (hors taxe & livraison).')
                        ->columnSpan(3),

                    TextInput::make('tax')
                        ->label('Taxe')
                        ->numeric()->minValue(0)->step('0.01')->required()
                        ->helperText('Montant total des taxes appliquées.')
                        ->columnSpan(3),

                    TextInput::make('delivery_costs')
                        ->label('Frais de livraison')
                        ->numeric()->minValue(0)->step('0.01')
                        ->helperText('Frais ajoutés pour la livraison (facultatif).')
                        ->columnSpan(3),

                    TextInput::make('total_amount')
                        ->label('Total à payer')
                        ->numeric()->minValue(0)->step('0.01')->required()
                        ->helperText('Montant final : produits + taxe + livraison.')
                        ->columnSpan(3),
                ])->columns(12),

                Section::make('Pièces & remarques')->schema([
                    FileUpload::make('prescription')
                        ->label("Ordonnances (images/PDF)")
                        ->multiple()
                                 // ->directory('orders/prescriptions')
                        ->disk('s3') // Filament uploade direct vers S3
                        ->directory('prescriptions')
                        ->visibility('private')
                        ->enableDownload()
                        ->enableOpen()
                        ->imageEditor()
                        ->helperText('Téléversez une ou plusieurs pièces (images/PDF). Stockées en JSON.'),

                    Textarea::make('note')
                        ->label('Note')
                        ->maxLength(1000)
                        ->helperText('Commentaire interne ou note du client.')
                        ->rows(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('prescription') // colonne réelle = 'image'
                    ->label('fichier')
                    ->getStateUsing(fn($record) => $record->mediaUrl('image')) // URL finale
                    ->size(64)
                    ->square()
                    ->defaultImageUrl(asset('assets/images/default.jpg')) // 👈 évite l’icône cassée
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->mediaUrl('prescription', ttl: 5)), // clic = grande image

                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('customer.name')
                //     ->label('Client')
                //     ->searchable(),

                                                       // TextColumn::make('pharmacy.name')
                                                       //     ->label('Pharmacie')
                                                       //     ->toggleable(),
                TextColumn::make('customer.firstname') // pas "customer.name" si tu n'as pas ce champ
                    ->label('Client')
                    ->searchable(),

                TextColumn::make('pharmacy.name') // ✅ cohérent avec l’alias ajouté
                    ->label('Pharmacie')
                    ->toggleable(),
                BadgeColumn::make('order_status')
                    ->label('Statut')
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'created'  => 'Créée',
                        'pending'  => 'En attente',
                        'paid'     => 'Payée',
                        'canceled' => 'Annulée',
                        'accepted' => 'Acceptée',
                        default    => $state,
                    })
                    ->color(fn(string $state) => match ($state) {
                        'created'  => 'gray',
                        'pending'  => 'warning',
                        'paid'     => 'success',
                        'canceled' => 'danger',
                        'accepted' => 'info',
                        default    => 'secondary',
                    }),

                TextColumn::make('amount')
                    ->label('Produits')
                // ->formatStateUsing(fn ($v) => number_format((float) $v, 2, '.', ' '))
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ') . ' ' . ($record->currency ?? 'USD')
                    )
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('tax')
                    ->label('Taxe')
                // ->formatStateUsing(fn ($v) => number_format((float) $v, 2, '.', ' '))
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.')
                    )
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('delivery_costs')
                    ->label('Livraison')
                // ->formatStateUsing(fn ($v) => $v === null ? '—' : number_format((float) $v, 2, '.', ' '))
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ') . ' ' . ($record->currency ?? 'USD')
                    )
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('latestShipment.deliveryPerson.fullname')
                    ->label('Livreur')
                    ->placeholder('—'),
                TextColumn::make('total_amount')
                    ->label('Total')
                // ->formatStateUsing(fn ($v) => number_format((float) $v, 2, '.', ' '))
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ') . ' ' . ($record->currency ?? 'USD')
                    )
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('order_status')
                    ->label('Statut commande')
                    ->options([
                        'created'  => 'Créée',
                        'pending'  => 'En attente',
                        'paid'     => 'Payée',
                        'canceled' => 'Annulée',
                        'accepted' => 'Acceptée',
                    ]),
                SelectFilter::make('status')
                    ->label('Actif ?')
                    ->options([1 => 'Actif', 0 => 'Inactif']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),

                /* 1) VALIDER la commande
             * Visible seulement si le statut n’est pas "validated"
             */
                Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn(Model $record) => $record->order_status === 'pending') // ⬅️ adapte (enum/int ?)
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        if ($record->order_status === 'accepted') {
                            Notification::make()->title('Déjà validée')->warning()->send();
                            return;
                        }
                        if (!$record->prescription) {
                            Notification::make()->title('Aucune prescription trouvée!')->danger()->send();
                            return;
                        }

                        $record->order_status = 'accepted'; // ⬅️ adapte
                        $record->updated_by   = Auth::id(); // ⬅️ si colonne existe
                        $record->save();

                        Notification::make()
                            ->title('Commande validée')
                            ->success()
                            ->send();
                    }),
                /* 2) VOIR L’ORDONNANCE (image ou PDF)
             * Visible si with_prescription = true et qu’au moins 1 fichier est présent.
             * Le champ peut être un STRING (clé S3 ou URL) ou un JSON d’URLs/clé(s).
             */
                Action::make('seePrescription')
                    ->label('Voir ordonnance')
                    ->icon('heroicon-m-document-magnifying-glass')
                    ->visible(function (Model $record) {
                        if (! $record->with_prescription) {
                            return false;
                        }

                        $val = $record->prescription ?? $record->prescription ?? null; // ⬅️ ADAPTE ton champ
                        return ! empty($val);
                    })
                    ->url(function (Model $record) {
                                                                                         // 1) Récupère la 1ère valeur (string ou tableau)
                        $raw   = $record->prescription ?? $record->prescription ?? null; // ⬅️ ADAPTE
                        $first = is_array($raw) ? (collect($raw)->first()) : $raw;
                        if (! is_string($first) || $first === '') {
                            return '#';
                        }

                        // 2) Transforme URL complète -> clé S3 (si besoin)
                        $key = $first;
                        if (preg_match('#^https?://#i', $first)) {
                            $parts  = parse_url($first);
                            $key    = ltrim($parts['path'] ?? '', '/'); // "bucket/dir/file" ou "dir/file"
                            $bucket = config('filesystems.disks.s3.bucket');
                            if ($bucket && Str::startsWith($key, $bucket . '/')) {
                                $key = substr($key, strlen($bucket) + 1);
                            }
                        }

                        // 3) URL signée (bucket privé)
                        return Storage::disk('s3')->temporaryUrl($key, now()->addMinutes(30));
                    })
                    ->openUrlInNewTab(),
                /* 3) AFFECTER À UN LIVREUR (après paiement)
             * Visible si status = 'paid'
             * Ouvre un formulaire pour choisir le livreur + (option) SMS de notif
             */

                Action::make('assignCourier')
                    ->label("Affecter à un livreur")
                    ->icon('heroicon-m-truck')
                    ->color('primary')
                    ->visible(function (Model $record) {
                        if ($record->order_status === 'paid' && ! $record->latestShipment) {
                            return true;
                        } else if ($record->latestShipment) {
                            return false;
                        }
                    })
                    ->form([
                                                    // ⚠️ On n'utilise PAS ->relationship() pour éviter d'écrire sur chem_orders
                                                    // Select::make('assignee_id')
                                                    //     ->label('Livreur')
                                                    //     ->relationship('deliveryPerson', 'firstname')
                                                    //                                                              // si tu as une relation: ->relationship('driver', 'name')
                                                    //                                                              // ->options(fn() => User::query()->pluck('fullname', 'id')->toArray())
                                                    //     ->getOptionLabelFromRecordUsing(fn(User $u) => $u->fullname) // <-- utilise l'accessor
                                                    //     ->searchable(['firstname', 'lastname', 'phone', 'email'])
                                                    //     ->preload()
                                                    //     ->dehydrated(true) // <-- clé: n’écrit JAMAIS sur le record
                                                    //     ->required(),
                        
                          Select::make('assignee_id')
    ->label('Livreur')
    ->dehydrated(false) // ne pas écrire sur chem_orders
    ->searchable()
    ->preload()
    ->getSearchResultsUsing(fn (string $search) =>
        CourierHelper::freeCourierOptions($search, now())
    )
    ->getOptionLabelUsing(fn ($value) =>
        optional(\App\Models\User::find($value))->fullname
    )
    ->noSearchResultsMessage('Aucun livreur disponible')
    ->required(),
                           
                        TextInput::make('estimated_delivery')
                            ->label('Temps estimé (minutes)')
                            ->numeric()->minValue(1)->maxValue(1440),

                        Toggle::make('notify_sms')
                            ->label('Notifier le livreur par SMS')
                            ->default(true),

                        Textarea::make('message')
                            ->label('Message SMS')
                            ->default('Nouvelle commande à livrer. Merci de vous connecter à l’application pour les détails.')
                            ->rows(3)
                            ->visible(fn(callable $get) => (bool) $get('notify_sms')),
                    ])
                    ->action(function (array $data, $record) {
                        // dd($data);
                        // --- Vérifs minimales avant création (évite erreurs SQL NOT NULL)
                        $customerId    = $record->customer_id ?? $record->user_id;
                        $customerPhone = $record->customer_phone ?? $record->customer->phone;
                        $addressId     = $record->address_id ?? $record->customer->customerAdresse->first()->id;
                        $addressRef    = $record->address_reference ?? null;

                        // // if (! $customerId || ! $customerPhone || ! $addressId) {
                        // if (! $customerId || ! $customerPhone|| ! $addressId) {
                        //     Notification::make()
                        //         ->title('Commande incomplète')
                        //         ->body("Client / téléphone / adresse requis pour créer l’expédition.")
                        //         ->danger()->send();
                        //     return;
                        // }
                        $missing = [];
                        if (! $customerId) {
                            $missing[] = 'client';
                        }

                        if (! $customerPhone) {
                            $missing[] = 'téléphone';
                        }

                        if (! $addressId) {
                            $missing[] = 'adresse';
                        }

                        if ($missing) {
                            Notification::make()
                                ->title('Commande incomplète')
                                ->body('Il manque : ' . implode(', ', $missing) . '.')
                                ->danger()
                                ->send();
                            return;
                        }
                        try {
                            DB::transaction(function () use ($data, $record, $customerId, $customerPhone, $addressId, $addressRef) {

                                // --- 1) Tracking
                                $tracking = static::makeTrackingNumber();

                                // --- 2) Créer l’expédition (chem_shipments)
                                ChemShipment::create([
                                    'status'             => 1,
                                    'created_by'         => Auth::id(),
                                    'updated_by'         => Auth::id(),
                                    'order_id'           => (int) $record->id,
                                    'delivery_person_id' => (int) $data['assignee_id'],
                                    'customer_id'        => (int) $customerId,
                                    'customer_phone'     => (string) $customerPhone,
                                    'address_id'         => (int) $addressId,
                                    'address_reference'  => $addressRef,
                                    'shipment_status'    => 'preparation',
                                    'tracking_number'    => $tracking,
                                    'shipped_at'         => now(),
                                    'estimated_delivery' => $data['estimated_delivery'] ?? null,
                                ]);

                                // --- 3) Mettre la commande en "assignée" SANS toucher à delivery_person_id
                                // $record->order_status = 'assigned'; // adapte si tu utilises une enum/int
                                // $record->updated_by   = Auth::id(); // si la colonne existe
                                // $record->save();
                                // 3) Mettre la commande en "assigned" SANS toucher au record en mémoire
                                $record->newQuery()->whereKey($record->getKey())->update([
                                    // 'order_status' => 'assigned',
                                    'updated_by' => Auth::id(),
                                    'updated_at' => now(),
                                ]);

                                // 4) SMS au livreur (optionnel)
                                if (! empty($data['notify_sms'])) {
                                    $driver = User::find($data['assignee_id']);
                                    if ($driver && ! empty($driver->phone)) {
                                        Sms::send($driver->phone, $data['message']);
                                    }
                                }
                            });
                            Notification::make()
                                ->title('Commande affectée au livreur et expédition créée')
                                ->success()->send();
                        } catch (\Throwable $e) {
                            // ❌ Erreur attrapée → pas de succès
                            report($e);
                            \Filament\Notifications\Notification::make()
                                ->title("Erreur lors de l'affectation")
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // … garde tes actions "Edit", "View", etc. si tu les as
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Supprimer la sélection'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ChemOrderResource\RelationManagers\ItemsRelationManager::class,
            ChemOrderResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemOrders::route('/'),
            'create' => Pages\CreateChemOrder::route('/create'),
            'view'   => ChemOrderResource\Pages\ViewChemOrder::route('/{record}'),
            'edit'   => Pages\EditChemOrder::route('/{record}/edit'),
        ];
    }
    protected static function makeTrackingNumber(): string
    {
        // Format: SH-YYMMDD-XXXXXXXX (<= 30 chars)
        do {
            $candidate = 'SH-' . now()->format('ymd') . '-' . Str::upper(Str::random(8));
        } while (ChemShipment::where('tracking_number', $candidate)->exists());

        return $candidate;
    }
}
