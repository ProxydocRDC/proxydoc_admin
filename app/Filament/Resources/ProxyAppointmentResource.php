<?php
namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\ProxyAppointment;

use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\KeyValue;;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\ProxyAppointmentResource\Pages;

class ProxyAppointmentResource extends Resource
{
    protected static ?string $model           = ProxyAppointment::class;
    protected static ?string $navigationIcon  = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Rendez-vous';
    protected static ?string $modelLabel      = 'Rendez-vous';
    protected static ?string $navigationGroup = 'Proxydoc';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Group::make([
                Section::make("Fiche RDV")->schema([
                    Hidden::make('created_by')->default(fn() => Auth::id()),
                    Hidden::make('updated_by')->default(fn() => Auth::id())->dehydrated(),

                    Toggle::make('status')->label('Actif')->default(true)
                        ->onColor('success')->offColor('danger')->required()
                        ->columnSpan(4),

                    // Relations (adapte les relationships si tes modèles existent)
                    Select::make('patient_id')->label('Patient')
                        ->relationship('patient', 'fullname') // ou 'name' selon ton modèle
                        ->searchable()->preload()->required()->columnSpan(4),

                    Select::make('doctor_user_id')->label('Médecin (user)')
                        ->relationship('doctorUser', 'email') // relation vers User
                        ->searchable()->preload()->columnSpan(4),

                    Select::make('service_id')->label('Service')
                        ->relationship('service', 'name')->searchable()->preload()->required()->columnSpan(4),

                    Select::make('schedule_id')->label('Agenda')
                        ->relationship('schedule', 'id')->preload()->columnSpan(4),

                    DateTimePicker::make('scheduled_at')->label('Date & heure')
                        ->seconds(false)->required()->columnSpan(4),

                    TextInput::make('slot_duration')->label('Durée (min)')
                        ->numeric()->minValue(5)->maxValue(480)->required()->columnSpan(4),

                    Toggle::make('is_immediate')->label('Prise en charge immédiate')->columnSpan(4),

                    Select::make('appointment_status')->label('Statut du RDV')
                        ->options([
                            'created'   => 'Créé',
                            'pending'   => 'En attente',
                            'confirmed' => 'Confirmé',
                            'canceled'  => 'Annulé',
                            'completed' => 'Terminé',
                            'no_show'   => 'Absent',
                        ])->default('created')->required()->columnSpan(4),

                    Select::make('communication_method')->label('Mode')
                        ->options([
                            'chat'  => 'Chat',
                            'audio' => 'Audio',
                            'video' => 'Vidéo',
                        ])->default('chat')->required()->columnSpan(4),

                    Toggle::make('paid')->label('Payé')->columnSpan(4),
                    Toggle::make('is_subscription')->label('Abonnement')->columnSpan(4),

                    TextInput::make('subscription_id')->numeric()->label('Abonnement ID')->columnSpan(4),
                    TextInput::make('payment_id')->numeric()->label('Paiement ID')->columnSpan(4),

                    // JSON "symptoms"
                    KeyValue::make('symptoms')->label('Symptômes (clé/valeur)')->columnSpan(12)
                        ->reorderable()->addButtonLabel('Ajouter'),

                    TextInput::make('complaint_title')->label('Titre du malaise')->maxLength(60)->columnSpan(6),
                    TextInput::make('complaint_since')->label('Depuis')->maxLength(50)->columnSpan(6),
                    Textarea::make('complaint_description')->label('Description')->rows(3)->columnSpan(12),

                    TextInput::make('height_cm')->numeric()->step('0.01')->label('Taille (cm)')->columnSpan(4),
                    TextInput::make('weight_kg')->numeric()->step('0.01')->label('Poids (kg)')->columnSpan(4),
                    TextInput::make('blood_pressure')->label('Tension')->maxLength(20)->columnSpan(4),
                    TextInput::make('heart_rate')->numeric()->label('BPM')->columnSpan(4),
                    TextInput::make('temperature_c')->numeric()->step('0.1')->label('Température (°C)')->columnSpan(4),

                    TextInput::make('chat_room_id')->label('ID salle (chat/appel)')
                        ->maxLength(12000)->columnSpan(12),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                ToggleColumn::make('status')->label('Actif'),
                TextColumn::make('patient.fullname')->label('Patient')->searchable(),
                TextColumn::make('doctorUser.email')->label('Médecin')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service.name')->label('Service')->searchable(),
                TextColumn::make('scheduled_at')->dateTime('Y-m-d H:i')->label('RDV')->sortable(),
                BadgeColumn::make('appointment_status')
                    ->label('Statut')
                    ->formatStateUsing(fn(string $state): string => [
                        'created'   => 'Créé',
                        'pending'   => 'En attente',
                        'confirmed' => 'Confirmé',
                        'canceled'  => 'Annulé',
                        'completed' => 'Terminé',
                        'no_show'   => 'Absent',
                    ][$state] ?? ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'primary' => 'created',
                        'warning' => 'pending',
                        'success' => ['confirmed', 'completed'],
                        'danger'  => 'canceled',
                        'gray'    => 'no_show',
                    ]),
                TextColumn::make('communication_method')->label('Mode')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('appointment_status')
                    ->options([
                        'created'  => 'Créé', 'pending'     => 'En attente', 'confirmed' => 'Confirmé',
                        'canceled' => 'Annulé', 'completed' => 'Terminé', 'no_show'      => "Absent",
                    ]),
                Tables\Filters\TernaryFilter::make('paid')->label('Payé'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProxyAppointments::route('/'),
            'create' => Pages\CreateProxyAppointment::route('/create'),
            'view'   => Pages\ViewProxyAppointment::route('/{record}'),
            'edit'   => Pages\EditProxyAppointment::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by']           = $data['created_by'] ?? Auth::id();
        $data['updated_by']           = $data['updated_by'] ?? Auth::id();
        $data['status']               = $data['status'] ?? 1;
        $data['appointment_status']   = $data['appointment_status'] ?? 'created';
        $data['communication_method'] = $data['communication_method'] ?? 'chat';
        return $data;
    }
    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
