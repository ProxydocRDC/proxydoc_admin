<?php
namespace App\Filament\Resources;

use App\Filament\Resources\UserSubscriptionResource\Pages;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Support\Sms;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
                TextColumn::make('user.firstname')->label('Utilisateur')
                    ->description(fn($record) => $record->user?->phone)->searchable(),
                TextColumn::make('plan.name')->label('Plan')->searchable(),
                TextColumn::make('seats')->label('Places')->alignRight(),
                TextColumn::make('start_date')->label('Début')->date(),
                TextColumn::make('end_date')->label('Fin')->date('d/m/y'),
                BadgeColumn::make('days_remaining')
                    ->label('Jours restants')
                    ->getStateUsing(fn($record) => $record->days_remaining)
                    ->formatStateUsing(fn($state) => $state !== null && $state < 0
                            ? 'Expiré'
                            : ($state === null ? '—' : $state . ' j'))
                    ->colors([
                        'gray'    => fn($state)    => $state !== null && $state < 0,
                        'danger'  => fn($state)  => $state !== null && $state <= 2 && $state >= 0,
                        'warning' => fn($state) => $state !== null && $state > 2 && $state <= 5,
                        'success' => fn($state) => $state !== null && $state > 5,
                    ]),

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
                Filter::make('expire_5j')
                    ->label('Expire ≤ 5 jours')
                    ->query(function (Builder $q) {
                        // dd($q->expiringWithin(5));
                        $from = now()->startOfDay();
                        $to   = now()->addDays(5)->endOfDay();

                        return $q->where('subscription_status', 'active')
                            ->whereBetween('end_date', [$from, $to]);
                    }),
                // Filter::make('actifs')->label('Actifs')
                //     ->query(fn(Builder $q) => $q->active()),
                Tables\Filters\SelectFilter::make('subscription_status')->label('Statut')->options([
                    'pending' => 'En attente', 'active' => 'Actif', 'expired' => 'Expiré', 'cancelled' => 'Annulé',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('send_sms')
                    ->label('Envoyer SMS')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(4)
                            ->default(
                                "Bonjour {name}, votre abonnement {plan} expire le {end_date} " .
                                "(dans {days} jours). Renouvelez ici : {url}"
                            ),
                    ])
                    ->action(function (EloquentCollection $records, array $data): void {
                        foreach ($records as $record) {
                            /** @var \App\Models\UserSubscription $record */
                            $user = $record->user;
                            if (! $user?->phone) {
                                continue;
                            }

                            $msg = strtr($data['message'], [
                                '{name}'     => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: ($user->name ?? 'client'),
                                '{plan}'     => $record->plan->name ?? '',
                                '{end_date}' => optional($record->end_date)->format('d/m/Y'),
                                '{days}'     => max(0, (int) ($record->days_remaining ?? 0)),
                                '{url}'      => config('app.url'),
                            ]);

                            Sms::send($user->phone, $msg);
                        }
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
