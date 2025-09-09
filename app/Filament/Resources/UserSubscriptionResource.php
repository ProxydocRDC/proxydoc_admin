<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserSubscriptionResource\Pages;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{Group, Section, TextInput, DatePicker, Select};
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Illuminate\Support\Carbon;

class UserSubscriptionResource extends \Filament\Resources\Resource
{
    protected static ?string $model = UserSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?string $navigationLabel = 'Abonnements utilisateurs';
    protected static ?int    $navigationSort  = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Abonnement')->schema([
                    Select::make('user_id')->label('Utilisateur')
                        ->relationship('user', 'firstname') // adapte si besoin
                        ->searchable()->preload()->required()->columnSpan(4),

                    Select::make('plan_id')->label('Plan')
                        ->relationship('plan', 'name')
                        ->searchable()->preload()->required()
                        ->reactive()->columnSpan(4)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                            if (! $state) return;
                            $months = SubscriptionPlan::find($state)?->periodicity ?? 1;
                            $start  = $get('start_date') ? Carbon::parse($get('start_date')) : Carbon::today();
                            $set('end_date', $start->copy()->addMonths($months)->toDateString());
                        }),

                    TextInput::make('seats')->label('Places')->columnSpan(4)
                        ->numeric()->minValue(1)->default(1)->required(),

                    DatePicker::make('start_date')->label('Début')
                        ->default(today())->required()->columnSpan(4)
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            $months = optional(SubscriptionPlan::find($get('plan_id')))->periodicity ?? 1;
                            $set('end_date', Carbon::parse($state)->addMonths($months)->toDateString());
                        }),

                    DatePicker::make('end_date')->label('Fin')->required()->columnSpan(4),

                    Select::make('subscription_status')->label('Statut')
                        ->options([
                            'pending'  => 'En attente',
                            'active'   => 'Actif',
                            'expired'  => 'Expiré',
                            'cancelled'=> 'Annulé',
                        ])->default('pending')->required()->columnSpan(4),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.firstname')->label('Utilisateur')->searchable(),
                TextColumn::make('plan.name')->label('Plan')->searchable(),
                TextColumn::make('seats')->label('Places')->alignRight(),
                TextColumn::make('start_date')->label('Début')->date(),
                TextColumn::make('end_date')->label('Fin')->date(),
                BadgeColumn::make('subscription_status')->label('Statut')->colors([
                    'warning' => 'pending',
                    'success' => 'active',
                    'danger'  => 'expired',
                    'gray'    => 'cancelled',
                ])->formatStateUsing(fn (string $state) => [
                    'pending'=>'En attente','active'=>'Actif','expired'=>'Expiré','cancelled'=>'Annulé'
                ][$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_status')->label('Statut')->options([
                    'pending'=>'En attente','active'=>'Actif','expired'=>'Expiré','cancelled'=>'Annulé',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUserSubscriptions::route('/'),
            'create' => Pages\CreateUserSubscription::route('/create'),
            'edit'   => Pages\EditUserSubscription::route('/{record}/edit'),
        ];
    }
}
