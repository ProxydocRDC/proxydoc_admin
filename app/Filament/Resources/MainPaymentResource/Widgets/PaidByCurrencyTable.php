<?php

namespace App\Filament\Resources\MainPaymentResource\Widgets;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use App\Models\MainPayment;
use Illuminate\Support\Facades\DB;

class PaidByCurrencyTable extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
     protected static ?string $heading = 'Montant payé par Devise';

    protected function getTableQuery(): Builder
    {
        $from = now()->subDays(30)->startOfDay();

        return MainPayment::query()
            ->select([
                'currency',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as tx_count'),
                DB::raw('SUM(CASE WHEN payment_status = 2 THEN amount ELSE 0 END) as total_approved'),
            ])
            ->where('created_at', '>=', $from)
            ->groupBy('currency')
            ->orderByDesc('total_amount');
    }

    // 🔑 Fournir une clé unique pour chaque ligne du tableau
    public function getTableRecordKey($record): string
    {
        // 'USD' / 'CDF' → suffisant pour ce tableau agrégé
        return (string) ($record->currency ?? uniqid('row_', true));
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('currency')->label('Devise')->badge(),
            Tables\Columns\TextColumn::make('tx_count')->label('Transactions')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('total_amount')
                ->label('Total payé')
                ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2, ',', ' ') . ' ' . ($r->currency ?? '')),
            Tables\Columns\TextColumn::make('total_approved')
                ->label('Payé approuvé')
                ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2, ',', ' ') . ' ' . ($r->currency ?? '')),
        ];
    }
}
