<?php

namespace App\Filament\Resources\MainPaymentResource\Pages;

use App\Filament\Resources\MainPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListMainPayments extends ListRecords
{
    protected static string $resource = MainPaymentResource::class;

    public function mount(): void
    {
        parent::mount();

        $filters = request()->query('tableFilters', []);
        if (! empty($filters)) {
            $this->tableFilters = array_merge($this->tableFilters ?? [], $filters);
        }
    }

    // 👉 doit être public
    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\MainPaymentResource\Widgets\PaymentsStats::class,
            \App\Filament\Resources\MainPaymentResource\Widgets\PaymentsByMethodChart::class,
            \App\Filament\Resources\MainPaymentResource\Widgets\PaidByCurrencyTable::class, // ← plein écran
        ];
    }

    // 👉 public aussi
    public function getHeaderWidgetsColumns(): int|array
    {
        // Stats + Chart côte-à-côte, puis le tableau en 'full' passera sur une nouvelle ligne
        return [
            'sm' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
