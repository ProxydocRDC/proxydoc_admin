<?php
namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Forms\Get;

use Filament\Forms\Set;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\BadgeColumn;

use Filament\Forms\Components\DatePicker;

use App\Filament\Resources\UserSubscriptionResource\Pages;
use Filament\Forms\Components\TextInput;use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;use Filament\Tables\Table;

class UserSubscriptionResource extends \Filament\Resources\Resource
{
    protected static ?string $model = UserSubscription::class;

    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?string $navigationLabel = 'Abonnements utilisateurs';
    protected static ?int $navigationSort     = 20;

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
                            if (! $state) {
                                return;
                            }

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
                            'pending'   => 'En attente',
                            'active'    => 'Actif',
                            'expired'   => 'Expiré',
                            'cancelled' => 'Annulé',
                        ])->default('pending')->required()->columnSpan(4)
                        ->disabled(function (Get $get) {
                            // Champ désactivé si l’abonnement est "en cours" au moment de l’édition
                            $status = $get('subscription_status');
                            $start  = $get('start_date');
                            $end    = $get('end_date');

                            if (! $status || ! $start || ! $end) {
                                return false;
                            }

                            $today = Carbon::today();
                            try {
                                $start = Carbon::parse($start);
                                $end   = Carbon::parse($end);
                            } catch (\Throwable $e) {
                                return false;
                            }

                            return $status === 'active'
                            && $start->lte($today)
                            && $end->gte($today);
                        })
                        ->hint(function (Get $get) {
                            $status = $get('subscription_status');
                            $start  = $get('start_date');
                            $end    = $get('end_date');

                            if (! $status || ! $start || ! $end) {
                                return null;
                            }

                            $today = Carbon::today();
                            try {
                                $start = Carbon::parse($start);
                                $end   = Carbon::parse($end);
                            } catch (\Throwable $e) {
                                return null;
                            }

                            return ($status === 'active' && $start->lte($today) && $end->gte($today))
                                ? 'Statut verrouillé : abonnement en cours.'
                                : null;
                        }),
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
                ])->formatStateUsing(fn(string $state) => [
                    'pending' => 'En attente', 'active' => 'Actif', 'expired' => 'Expiré', 'cancelled' => 'Annulé',
                ][$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_status')->label('Statut')->options([
                    'pending' => 'En attente', 'active' => 'Actif', 'expired' => 'Expiré', 'cancelled' => 'Annulé',
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
