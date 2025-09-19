<?php

namespace App\Filament\Resources\MainPaymentResource\Widgets;

use App\Models\MainPayment;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecentPayments extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    // ✅ Signature compatible avec Filament v3
    protected function getTableQuery(): Builder|Relation|null
    {
        // Laisse Filament gérer la pagination; pas besoin de ->limit()
        return MainPayment::query()->latest('id');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('Y-m-d H:i')
                ->sortable(),

            Tables\Columns\TextColumn::make('method')
                ->label('Méthode')
                ->badge()
                ->formatStateUsing(fn ($state) => $state === MainPayment::METHOD_CARD ? 'Carte' : 'Mobile Money'),

            Tables\Columns\TextColumn::make('channel')->label('Canal'),

            Tables\Columns\TextColumn::make('currency')
                ->badge(),

            Tables\Columns\TextColumn::make('amount')
                ->label('Payé')
                ->money(fn ($record) => $record->currency)
                ->sortable(),

            Tables\Columns\TextColumn::make('payment_status')
                ->label('Statut')
                ->badge()
                ->color(fn ($record) => $record->payment_status_color)
                ->formatStateUsing(fn ($record) => $record->payment_status_label),

            Tables\Columns\TextColumn::make('telephone')->label('Téléphone'),
            Tables\Columns\TextColumn::make('gateway')->label('Gateway'),
        ];
    }
}
