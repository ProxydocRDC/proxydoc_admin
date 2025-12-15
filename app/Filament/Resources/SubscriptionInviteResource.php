<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionInviteResource\Pages;
use App\Models\SubscriptionInvite;
use Illuminate\Support\Str;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{Group, Section, TextInput, DateTimePicker, Select, Toggle};
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, BadgeColumn, IconColumn};
use Illuminate\Support\Facades\Auth;

class SubscriptionInviteResource extends \Filament\Resources\Resource
{
    protected static ?string $model = SubscriptionInvite::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?string $navigationLabel = 'Invitations';
    protected static ?int    $navigationSort  = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Invitation')->schema([
                      Hidden::make('created_by')->default(fn() => Auth::id()),
                    Hidden::make('updated_by')->default(fn() => Auth::id())->dehydrated(),
                    Select::make('subscription_id')->label('Abonnement')->columnSpan(4)
                        ->relationship('subscription', 'id')->searchable()->preload()->required(),
                    Select::make('role')->label('Rôle')->columnSpan(4)
                        ->options(['admin'=>'Admin','member'=>'Membre'])->default('member')->required(),
                    TextInput::make('email')->email()->maxLength(150)->columnSpan(4),
                    TextInput::make('phone')->tel()->maxLength(50)->columnSpan(4),
                    Select::make('user_id')->label('Utilisateur (si existant)')->columnSpan(4)
                        ->relationship('invitedUser', 'firstname')->searchable()->preload(),

                    TextInput::make('token')->label('Token')->columnSpan(4)
                        ->default(fn() => Str::random(40))->required()->maxLength(64),
                    DateTimePicker::make('expires_at')->label('Expire le')->columnSpan(4)
                        ->default(now()->addDays(7))->required(),
                    DateTimePicker::make('accepted_at')->label('Acceptée le')->columnSpan(4),
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
                TextColumn::make('email')->label('Email')->copyable(),
                TextColumn::make('phone')->label('Téléphone'),
                BadgeColumn::make('role')->colors(['primary'=>['admin','member']])
                    ->formatStateUsing(fn($state)=>['admin'=>'Admin','member'=>'Membre'][$state] ?? $state),
                TextColumn::make('expires_at')->label('Expire le')->dateTime(),
                IconColumn::make('is_expired')->label('Expirée ?')->boolean(),
                TextColumn::make('accepted_at')->label('Acceptée le')->dateTime(),
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
            'index'  => Pages\ListSubscriptionInvites::route('/'),
            'create' => Pages\CreateSubscriptionInvite::route('/create'),
            'edit'   => Pages\EditSubscriptionInvite::route('/{record}/edit'),
        ];
    }
     public static function getNavigationBadge(): ?string
    {

        $base = SubscriptionInvite::query();


        return (string) $base->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success'; // ou 'success', 'warning', etc.
    }
}
