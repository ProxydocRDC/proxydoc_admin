<?php
namespace App\Filament\Widgets;

use App\Models\UserSubscription;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ExpiringSubscriptionsTable extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Abonnements qui expirent (≤ 5 jours)';

    protected function getTableQuery(): Builder
    {
        return UserSubscription::query()
            ->with(['user', 'plan'])
            ->expiringWithin(5)
            ->orderBy('end_date');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('user.firstname')->label('Utilisateur')->searchable(),
            TextColumn::make('plan.name')->label('Plan')->searchable(),
            TextColumn::make('end_date')->label('Fin')->date('d/m/Y'),
            BadgeColumn::make('days_remaining')
                ->label('Jours')
                ->getStateUsing(fn($record) => $record->days_remaining)
                ->formatStateUsing(fn($state) => $state !== null && $state < 0 ? 'Expiré' : ($state.' j'))
                ->colors([
                    'danger'  => fn($state) => $state !== null && $state <= 2 && $state >= 0,
                    'warning' => fn($state) => $state !== null && $state > 2 && $state <= 5,
                    'gray'    => fn($state) => $state !== null && $state < 0,
                ]),
        ];
    }
}
