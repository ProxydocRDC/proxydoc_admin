<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionMemberResource\Pages;
use App\Models\SubscriptionMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{Group, Section, TextInput, DateTimePicker, Select, Toggle};
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};

class SubscriptionMemberResource extends \Filament\Resources\Resource
{
    protected static ?string $model = SubscriptionMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?string $navigationLabel = 'Membres';
    protected static ?int    $navigationSort  = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Membre')->schema([
                    Select::make('subscription_id')->label('Abonnement')->columnSpan(4)
                        ->relationship('subscription', 'id')->searchable()->preload()->required(),

                    Select::make('role')->label('Rôle')->columnSpan(4)
                        ->options(['owner'=>'Propriétaire', 'admin'=>'Admin', 'member'=>'Membre'])
                        ->default('member')->required(),

                    Select::make('member_type')->label('Type de membre')
                        ->options(['user'=>'Utilisateur', 'patient'=>'Patient'])
                        ->reactive()->required()->columnSpan(4),

                    Select::make('user_id')->label('Utilisateur')->columnSpan(4)
                        ->relationship('user', 'firstname')->searchable()->preload()
                        ->visible(fn (Get $get) => $get('member_type') === 'user'),

                    Select::make('patient_id')->label('Patient')->columnSpan(4)
                        ->relationship('patient', 'fullname') // adapte si besoin
                        ->searchable()->preload()
                        ->visible(fn (Get $get) => $get('member_type') === 'patient'),

                    TextInput::make('seat_count')->label('Places')->columnSpan(4)
                        ->numeric()->minValue(1)->default(1)->required(),

                    DateTimePicker::make('accepted_at')->label('Accepté le')->columnSpan(4),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id','desc')
            ->columns([
                TextColumn::make('subscription.id')->label('Abonnement')->sortable(),
                BadgeColumn::make('role')->label('Rôle')->colors([
                    'primary' => ['owner','admin','member'],
                ])->formatStateUsing(fn($state) => ['owner'=>'Propriétaire','admin'=>'Admin','member'=>'Membre'][$state] ?? $state),
                TextColumn::make('member_type')->label('Type'),
                TextColumn::make('user.firstname')->label('Utilisateur')->toggleable(),
                TextColumn::make('patient.fullname')->label('Patient')->toggleable(),
                TextColumn::make('seat_count')->label('Places')->alignRight(),
                TextColumn::make('accepted_at')->label('Accepté le')->dateTime(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubscriptionMembers::route('/'),
            'create' => Pages\CreateSubscriptionMember::route('/create'),
            'edit'   => Pages\EditSubscriptionMember::route('/{record}/edit'),
        ];
    }
}
