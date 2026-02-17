<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ChemShipmentResource\Pages;
use App\Models\ChemShipment;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ChemShipmentResource extends Resource
{
    protected static ?string $model = ChemShipment::class;

    /** Menu & libellés */
    protected static ?string $navigationIcon   = 'heroicon-o-truck';
    protected static ?string $navigationGroup  = 'Logistique';
    protected static ?string $navigationLabel  = 'Livraisons';
    protected static ?string $modelLabel       = 'Livraison';
    protected static ?string $pluralModelLabel = 'Livraisons';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Informations de suivi')->schema([
                        Hidden::make('created_by')->default(Auth::id()),

                        Select::make('status')
                            ->label('Statut (technique)')
                            ->options([1 => 'Actif', 0 => 'Inactif'])
                            ->default(1)
                            ->helperText('Statut interne d’activation de l’enregistrement.'),

                        Select::make('shipment_status')
                            ->label('Statut de livraison')
                            ->options([
                                'preparation' => 'Préparation',
                                'shipped'     => 'Expédiée',
                                'transit'     => 'En transit',
                                'delivered'   => 'Livrée',
                                'canceled'    => 'Annulée',
                            ])
                            ->required()
                            ->helperText('État actuel du colis dans le flux logistique.'),

                        TextInput::make('tracking_number')
                            ->label('N° de suivi')
                            ->required()
                            ->maxLength(30)
                            ->unique(
                                table: 'chem_shipments',
                                column: 'tracking_number',
                                ignoreRecord: true
                            )
                            ->helperText('Identifiant unique interne de suivi (ex. TRK-2025-000123).'),
                    ])->columns(3),

                    Section::make('Références & acteurs')->schema([
                        Select::make('order_id')
                            ->label('Commande')
                            ->relationship('order', 'id') // adapte le champ affiché
                            ->searchable()->preload()->required()
                            ->helperText('Commande d’origine liée à cette livraison.'),

                        Select::make('delivery_person_id')
                            ->label('Livreur')
                            ->relationship('deliveryPerson', 'firstname') // ou firstname
                            ->searchable()->preload()->required()
                            ->helperText('Utilisateur chargé de livrer le colis.'),

                        Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'firstname')
                            ->searchable()->preload()->required()
                            ->helperText('Client destinataire de la commande.'),

                        TextInput::make('customer_phone')
                            ->label('Téléphone client')
                            ->tel()
                            ->maxLength(20)
                            ->required()
                            ->helperText('Numéro de contact du client (ex. +243 99 000 00 00).'),
                    ])->columns(4),

                    Section::make('Adresse')->schema([
                        Select::make('address_id')
                            ->label('Adresse client')
                            ->relationship('address', 'line1') // ex: “Domicile – Gombe”
                            ->searchable()->preload()->required()
                            ->helperText('Adresse de livraison enregistrée pour ce client.'),

                        Textarea::make('address_reference')
                            ->label('Référence / Indications')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Repères de localisation : portail bleu, 3e étage, près de ...'),
                    ])->columns(2),

                    Section::make('Chronologie')->schema([
                        DateTimePicker::make('shipped_at')
                            ->label('Début livraison')
                            ->seconds(false)
                            ->required()
                            ->helperText('Date & heure de départ du livreur.'),

                        TextInput::make('estimated_delivery')
                            ->label('Délai estimé (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Durée estimée entre départ et livraison (en minutes).'),

                        DateTimePicker::make('delivered_at')
                            ->label('Livrée le')
                            ->seconds(false)
                            ->helperText('Renseigner lorsque la livraison est effectivement réalisée.'),
                    ])->columns(3),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tracking_number')
                    ->label('Suivi')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copié !')
                    ->sortable(),

                TextColumn::make('order.reference')
                    ->label('Commande')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('customer_phone')
                    ->label('Téléphone')
                    ->toggleable(),

                TextColumn::make('deliveryPerson.name')
                    ->label('Livreur')
                    ->toggleable(),

                BadgeColumn::make('shipment_status')
                    ->label('Statut')
                    ->colors([
                        'preparation' => 'gray',
                        'shipped'     => 'warning',
                        'transit'     => 'info',
                        'delivered'   => 'success',
                        'canceled'    => 'danger',
                    ])
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'preparation'                        => 'Préparation',
                        'shipped'                            => 'Expédiée',
                        'transit'                            => 'En transit',
                        'delivered'                          => 'Livrée',
                        'canceled'                           => 'Annulée',
                        default                              => $state,
                    }),

                TextColumn::make('shipped_at')
                    ->label('Départ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('delivered_at')
                    ->label('Arrivée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                // Durée réelle si livré, sinon durée écoulée
                TextColumn::make('duration')
                    ->label('Durée')
                    ->getStateUsing(function ($record) {
                        $start = $record->shipped_at ? Carbon::parse($record->shipped_at) : null;
                        if (! $start) {
                            return '—';
                        }

                        $end     = $record->delivered_at ? Carbon::parse($record->delivered_at) : now();
                        $minutes = $start->diffInMinutes($end);
                        $h       = intdiv($minutes, 60);
                        $m       = $minutes % 60;
                        return ($h ? "{$h}h " : '') . "{$m}m";
                    })
                    ->tooltip(fn($record) =>
                        $record->estimated_delivery
                        ? 'Estimé: ' . $record->estimated_delivery . ' min'
                        : null
                    ),

                // Retard / à l’heure
                BadgeColumn::make('eta_status')
                    ->label('État délai')
                    ->getStateUsing(function ($record) {
                        if (! $record->estimated_delivery || ! $record->shipped_at) {
                            return 'N/A';
                        }
                        $deadline  = Carbon::parse($record->shipped_at)->addMinutes((int) $record->estimated_delivery);
                        $delivered = $record->delivered_at ? Carbon::parse($record->delivered_at) : now();

                        return $delivered->greaterThan($deadline) ? 'En retard' : 'À l’heure';
                    })
                    ->colors([
                        'danger'  => 'En retard',
                        'success' => 'À l’heure',
                        'gray'    => 'N/A',
                    ]),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('shipment_status')
                    ->label('Statut de livraison')
                    ->options([
                        'preparation' => 'Préparation',
                        'shipped'     => 'Expédiée',
                        'transit'     => 'En transit',
                        'delivered'   => 'Livrée',
                        'canceled'    => 'Annulée',
                    ]),
                Filter::make('periode_depart')
                    ->label('Période de départ')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('to')->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn($q, $date) => $q->whereDate('shipped_at', '>=', $date))
                            ->when($data['to'] ?? null, fn($q, $date) => $q->whereDate('shipped_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),

                // Action rapide : marquer livré maintenant
                Action::make('markDelivered')
                    ->label('Marquer livré')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => $record->shipment_status !== 'delivered')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->shipment_status = 'delivered';
                        $record->delivered_at    = now();
                        $record->updated_by      = Auth::id();
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Livraison clôturée')
                            ->body("Le colis {$record->tracking_number} est marqué comme livré.")
                            ->sendToDatabase(Auth::user())
                            ->success()
                            ->send();
                    }),
                \App\Filament\Actions\TrashAction::make()->label('Mettre à la corbeille'),
            ])
            ->bulkActions([
                \App\Filament\Actions\TrashBulkAction::make()->label('Mettre à la corbeille'),
            ]);
    }

    public static function getRelations(): array
{
    return [
        \App\Filament\Resources\ChemShipmentResource\RelationManagers\EventsRelationManager::class,
        // ... tes autres relation managers (Items/Payments si besoin)
    ];
}


    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemShipments::route('/'),
            'create' => Pages\CreateChemShipment::route('/create'),
            'edit'   => Pages\EditChemShipment::route('/{record}/edit'),
        ];
    }
    /** Auto set auteurs */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
