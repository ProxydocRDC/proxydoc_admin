<?php

namespace App\Filament\Resources\ChemOrderResource\RelationManagers;

use Filament\Forms\Form;

// ⚠️ Ces deux imports manquants causent exactement ton erreur :
use Filament\Tables\Table;
use Filament\Forms\Components\Group;

// Imports des composants de formulaire
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;

// Imports des colonnes de table
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\RelationManagers\RelationManager;

// (facultatif) Notifications, actions, etc. si tu en utilises
// use Filament\Notifications\Notification;
class PaymentsRelationManager  extends RelationManager
{
     protected static string $relationship = 'payments';
    protected static ?string $title = 'Paiements';

    public function form(Form $form): Form
    {
        return $form->schema([
            Group::make([
                Section::make('Détails du paiement')->schema([
                    Hidden::make('created_by')->default(Auth::id()),

                    Select::make('status')
                        ->label('Statut (technique)')
                        ->options([1 => 'Actif', 0 => 'Inactif'])
                        ->default(1),

                    Select::make('payment_status')
                        ->label('Statut de paiement')
                        ->options([
                            1 => 'En cours',
                            2 => 'Approuvé',
                            3 => 'Rejeté',
                        ])
                        ->required()
                        ->helperText('État du paiement : 1=En cours, 2=Approuvé, 3=Rejeté.'),

                    Select::make('method')
                        ->label('Méthode')
                        ->options([
                            'mobile_money' => 'Mobile money',
                            'card'         => 'Carte',
                        ])
                        ->required()
                        ->helperText('Mode d’initiation du paiement.'),

                    TextInput::make('channel')
                        ->label('Canal')
                        ->maxLength(50)
                        ->helperText('Ex. M-Pesa, Orange Money, Airtel, Visa…'),

                    TextInput::make('telephone')
                        ->label('Téléphone')
                        ->tel()->maxLength(50)->required()
                        ->helperText('Numéro utilisé pour le paiement (si applicable).'),

                    TextInput::make('currency')
                        ->label('Devise')
                        ->maxLength(4)->required()
                        ->default('USD')
                        ->helperText('Ex. USD, CDF.'),

                    // amount est INT dans ta table (ex: centimes ?). total_amount est DECIMAL(16,3).
                    TextInput::make('amount')
                        ->label('Montant (int)')
                        ->numeric()->minValue(0)->required()
                        ->helperText('Champ INT brut (ex. centimes).'),

                    TextInput::make('total_amount')
                        ->label('Montant total')
                        ->numeric()->minValue(0)->step('0.001')->required()
                        ->helperText('Montant total dans la devise, avec 3 décimales.'),

                    DateTimePicker::make('created_at')
                        ->label('Payé le')
                        ->seconds(false)
                        ->default(now())
                        ->required(),

                    TextInput::make('reference')
                        ->label('Référence interne')
                        ->maxLength(100),

                    TextInput::make('provider_reference')
                        ->label('Réf. provider')
                        ->maxLength(1000),

                    TextInput::make('order_number')
                        ->label('N° paiement provider')
                        ->maxLength(1000),

                    TextInput::make('gateway')
                        ->label('Gateway')
                        ->maxLength(100)
                        ->helperText('API de paiement : Flexpay, Stripe, …'),

                    TextInput::make('ip_address')
                        ->label('IP client')
                        ->maxLength(100),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
         return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),

                BadgeColumn::make('payment_status')
    ->label('Statut')
    ->formatStateUsing(fn (int $state) => [
        1 => 'En cours',
        2 => 'Approuvé',
        3 => 'Rejeté',
    ][$state] ?? $state)
    ->color(fn (int $state) => [
        1 => 'warning',
        2 => 'success',
        3 => 'danger',
    ][$state] ?? 'gray'),


                TextColumn::make('method')
                    ->label('Méthode')
 ->formatStateUsing(fn($state, $record) =>
                        $state === 'mobile_money' ? 'Mobile money' : 'Carte'
                    ),
                    // ->formatStateUsing(fn ($m) => $m === 'mobile_money' ? 'Mobile money' : 'Carte'),

                TextColumn::make('channel')->label('Canal')->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Montant')
                    ->alignRight()
                    ->formatStateUsing(fn($state, $record) =>
                       number_format((float) $state, 3, '.', $record->currency)
                    )
                    // ->formatStateUsing(fn ($v, $r) => number_format((float)$v, 3, '.', ' ') . ' ' . $r->currency)
                    ->sortable(),

                TextColumn::make('telephone')->label('Téléphone')->toggleable(),
                TextColumn::make('reference')->label('Réf.')->copyable()->toggleable(),
                TextColumn::make('provider_reference')->label('Réf. provider')->limit(18)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('order_number')->label('N° provider')->limit(18)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gateway')->label('Gateway')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
        ->headerActions([
            \Filament\Tables\Actions\CreateAction::make()->label('Ajouter une ligne'),
        ])
        ->actions([
            \Filament\Tables\Actions\EditAction::make(),
            \Filament\Tables\Actions\DeleteAction::make(),
        ])
        ->paginated(false);
    }
}
