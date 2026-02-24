<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\TableWidget as BaseWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class WeeklyRegistrationsTable extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Inscriptions (nouveaux comptes)';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getTableQuery(): Builder
    {
        return User::query()->orderByDesc('created_at');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('firstname')
                    ->label('Prénom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lastname')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('otp')
                    ->label('OTP')
                    ->formatStateUsing(fn (?string $state, User $record) => match (true) {
                        (int) $record->status === 5 && filled($state) => 'En attente',
                        (int) $record->status === 5 && empty($state) => '—',
                        default => 'Validé',
                    })
                    ->badge()
                    ->color(fn (?string $state, User $record) => (int) $record->status === 5 && filled($state) ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('processus_termine')
                    ->label('Processus terminé')
                    ->badge()
                    ->getStateUsing(fn (User $record) => $record->hasCompletedRegistration() ? 'Oui' : 'Non')
                    ->color(fn (User $record) => $record->hasCompletedRegistration() ? 'success' : 'warning')
                    ->tooltip(fn (User $record) => $record->status === 5
                        ? 'En attente validation OTP (tél: ' . ($record->phone ?? '—') . ')'
                        : null),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        0 => 'Supprimé',
                        1 => 'Activé',
                        2 => 'En attente',
                        3 => 'Désactivé',
                        4 => 'Validé, attente infos',
                        5 => 'En cours (OTP à valider)',
                        default => '—',
                    })
                    ->color(fn ($state) => match ((int) $state) {
                        0 => 'danger',
                        1 => 'success',
                        2 => 'warning',
                        3 => 'gray',
                        4 => 'info',
                        5 => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('default_role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn ($state) => [1 => 'Activé', 2 => 'Docteur', 3 => 'Livreur', 4 => 'Supprimé'][(int) $state] ?? '—')
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        4 => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date inscription')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->default([
                        'created_from' => now()->subWeek()->startOfDay()->format('Y-m-d'),
                        'created_to'   => now()->endOfDay()->format('Y-m-d'),
                    ])
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Du')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('created_to')
                            ->label('Au')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = $data['created_from'] ?? now()->subWeek()->startOfDay();
                        $to   = $data['created_to'] ?? now()->endOfDay();
                        return $query
                            ->whereDate('created_at', '>=', $from)
                            ->whereDate('created_at', '<=', $to);
                    }),
                Tables\Filters\Filter::make('processus')
                    ->form([
                        \Filament\Forms\Components\Select::make('value')
                            ->label('Processus')
                            ->options([
                                'termine'  => 'Terminé (tél. vérifié par OTP)',
                                'en_cours' => 'En cours (OTP à valider)',
                            ])
                            ->placeholder('— Tous —'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'termine'  => $query->where('status', '!=', 5),
                            'en_cours' => $query->where('status', 5),
                            default    => $query,
                        };
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        0 => 'Supprimé',
                        1 => 'Activé',
                        2 => 'En attente',
                        3 => 'Désactivé',
                        4 => 'Validé, attente infos',
                        5 => 'En cours (OTP à valider)',
                    ]),
            ])
            ->filtersFormColumns(2)
            ->paginated([10, 25, 50]);
    }
}
