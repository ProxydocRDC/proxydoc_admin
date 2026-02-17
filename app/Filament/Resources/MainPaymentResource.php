<?php

namespace App\Filament\Resources;

use App\Filament\Actions\TrashAction;
use App\Filament\Actions\TrashBulkAction;
use App\Filament\Concerns\HasTrashableRecords;
use App\Filament\Resources\MainPaymentResource\Pages;
use App\Models\MainPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MainPaymentsExport;

class MainPaymentResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model = MainPayment::class;

    protected static ?string $navigationIcon  = 'heroicon-m-credit-card';
    protected static ?string $navigationGroup = 'Finances';
    protected static ?string $pluralLabel     = 'Paiements';
    protected static ?string $label           = 'Paiement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations paiement')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('method')
                        ->label('Méthode')
                        ->options([
                            MainPayment::METHOD_MOBILE_MONEY => 'Mobile Money',
                            MainPayment::METHOD_CARD         => 'Carte',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('channel')
                        ->label('Canal / Opérateur')
                        ->placeholder('mpesa, orange, visa, mastercard...')
                        ->maxLength(50),

                    Forms\Components\Select::make('currency')
                        ->label('Devise')
                        ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                        ->required(),

                   Tables\Columns\TextColumn::make('amount')
    ->label('Payé')
    ->sortable()
    ->formatStateUsing(fn ($state, $record) =>
        number_format((float) $state, 2, ',', ' ') . ' ' . $record->currency
    ),



Tables\Columns\TextColumn::make('total_amount')
    ->label('Montant total')
    ->sortable()
    ->formatStateUsing(fn ($state, $record) =>
        number_format((float) $state, 2, ',', ' ') . ' ' . $record->currency
    ),

                    Forms\Components\TextInput::make('telephone')
                        ->label('Téléphone payeur')
                        ->required()->maxLength(50),

                    Forms\Components\Select::make('payment_status')
                        ->label('Statut')
                        ->options([
                            MainPayment::STATUS_PENDING  => 'En cours',
                            MainPayment::STATUS_APPROVED => 'Approuvé',
                            MainPayment::STATUS_REJECTED => 'Rejeté',
                        ])
                        ->default(MainPayment::STATUS_PENDING)
                        ->required(),

                    Forms\Components\TextInput::make('gateway')->maxLength(100),
                    Forms\Components\TextInput::make('order_number')->label('N° transaction')->maxLength(1000),
                    Forms\Components\TextInput::make('reference')->label('Référence interne')->maxLength(100),
                    Forms\Components\TextInput::make('provider_reference')->label('Référence gateway')->maxLength(1000),
                    Forms\Components\TextInput::make('ip_address')->label('IP')->maxLength(100),
                    Forms\Components\TextInput::make('order_id')->numeric()->label('Commande #'),
                    Forms\Components\TextInput::make('subscription_id')->numeric()->label('Abonnement #'),
                    Forms\Components\TextInput::make('appointment_id')->numeric()->label('RDV #'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('method')
                    ->label('Méthode')
                    ->badge()
                    ->color(fn ($state) => $state === MainPayment::METHOD_CARD ? 'info' : 'primary')
                    ->formatStateUsing(fn ($state) => $state === MainPayment::METHOD_CARD ? 'Carte' : 'Mobile Money')
                    ->searchable(),

                Tables\Columns\TextColumn::make('channel')->label('Canal')->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('currency')
                    ->badge()->color('gray')->label('Devise'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Payé')->money(fn ($record) => $record->currency)->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')->money(fn ($record) => $record->currency)->sortable()->toggleable(),

               Tables\Columns\TextColumn::make('payment_status')
    ->label('Statut')
    ->badge()
    ->icon(fn ($record) => match ($record->payment_status) {
        2 => 'heroicon-m-check-circle',
        3 => 'heroicon-m-x-circle',
        default => 'heroicon-m-clock',
    })
    ->color(fn ($record) => match ($record->payment_status) {
        2 => 'success',
        3 => 'danger',
        default => 'warning',
    })
    ->formatStateUsing(fn ($record) => match ($record->payment_status) {
        2 => 'Approuvé (Payé)',
        3 => 'Rejeté (Échec/Annulé)',
        default => 'En cours (En attente)',
    })
    ->tooltip(fn ($record) => $record->gateway
        ? "Gateway: {$record->gateway}" . ($record->order_number ? " • N°: {$record->order_number}" : '')
        : null),

                Tables\Columns\TextColumn::make('telephone')->label('Téléphone')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('gateway')->label('Gateway')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('order_number')->label('N° transaction')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reference')->label('Référence')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_by')->label('Créé par')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('method')
                    ->label('Méthode')
                    ->options([
                        MainPayment::METHOD_MOBILE_MONEY => 'Mobile Money',
                        MainPayment::METHOD_CARD         => 'Carte',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Statut')
                    ->options([
                        MainPayment::STATUS_PENDING  => 'En cours',
                        MainPayment::STATUS_APPROVED => 'Approuvé',
                        MainPayment::STATUS_REJECTED => 'Rejeté',
                    ]),
                SelectFilter::make('currency')
                    ->options(['USD' => 'USD', 'CDF' => 'CDF']),

                ...array_filter([static::getTrashFilter()]),
                Filter::make('created_at')
                    ->label('Période')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('to')->label('Au'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        return $q->when($data['from'] ?? null, fn ($qq) =>
                                $qq->whereDate('created_at', '>=', $data['from']))
                                ->when($data['to'] ?? null, fn ($qq) =>
                                $qq->whereDate('created_at', '<=', $data['to']));
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Exporter')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('to')->label('Au'),
                        Forms\Components\Select::make('status')->label('Statut')
                            ->options([
                                ''                          => '— Tous —',
                                MainPayment::STATUS_PENDING  => 'En cours',
                                MainPayment::STATUS_APPROVED => 'Approuvé',
                                MainPayment::STATUS_REJECTED => 'Rejeté',
                            ])->default(''),
                    ])
                    ->action(function (array $data) {
                        $from = $data['from'] ?? null;
                        $to   = $data['to']   ?? null;
                        $st   = $data['status'] !== '' ? (int) $data['status'] : null;

                        return Excel::download(new MainPaymentsExport($from, $to, $st), 'payments.xlsx');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                TrashAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    TrashBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        return static::applyTrashFilter($query);
    }

    // created_by / updated_by
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id() ?? 1;
        $data['updated_by'] = Auth::id() ?? 1;
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id() ?? 1;
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMainPayments::route('/'),
            'create' => Pages\CreateMainPayment::route('/create'),
            'edit' => Pages\EditMainPayment::route('/{record}/edit'),
            // 'view' => Pages\ViewMainPayment::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            MainPaymentResource\Widgets\PaymentsStats::class,
            MainPaymentResource\Widgets\RecentPayments::class,
            MainPaymentResource\Widgets\PaymentsByMethodChart::class,
        ];
    }
}
