<?php

namespace App\Filament\Resources\ChemPharmacyProductResource\Pages;

use App\Filament\Resources\ChemPharmacyProductResource;
use App\Models\ChemPharmacyProduct;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Session;

class SetQuantitiesProgress extends Page
{
    protected static string $resource = ChemPharmacyProductResource::class;

    protected static string $view = 'filament.resources.chem-pharmacy-product-resource.pages.set-quantities-progress';

    protected static bool $shouldRegisterNavigation = false;

    public int $total = 0;
    public int $processed = 0;
    public float $quantity = 0;
    public bool $completed = false;
    public bool $cancelled = false;

    public function getTitle(): string
    {
        return 'Mise à jour des quantités en cours';
    }

    public function mount(): void
    {
        $data = Session::get('set_quantities_data');
        if (! $data) {
            $this->redirect(ChemPharmacyProductResource::getUrl('index'));
            return;
        }

        $this->quantity = (float) ($data['quantity'] ?? 0);
        $this->total = ChemPharmacyProduct::query()->count();
        $this->processed = $data['processed'] ?? 0;

        $this->processChunk();
    }

    public function processChunk(): void
    {
        $data = Session::get('set_quantities_data');
        if (! $data) {
            $this->completed = true;
            return;
        }

        if (Session::get('set_quantities_cancelled')) {
            $this->cancelled = true;
            $this->completed = true;
            Session::forget(['set_quantities_data', 'set_quantities_cancelled']);
            Notification::make()
                ->title('Opération annulée')
                ->body("{$this->processed} produit(s) mis à jour avant l'annulation.")
                ->warning()
                ->send();
            return;
        }

        $chunkSize = 50;
        $quantity = (float) ($data['quantity'] ?? 0);
        $processed = $data['processed'] ?? 0;

        $ids = ChemPharmacyProduct::query()
            ->orderBy('id')
            ->offset($processed)
            ->limit($chunkSize)
            ->pluck('id');

        $updated = $ids->isEmpty() ? 0 : ChemPharmacyProduct::whereIn('id', $ids)->update(['stock_qty' => $quantity]);
        $processed += $ids->count();
        $data['processed'] = $processed;
        Session::put('set_quantities_data', $data);

        $this->processed = $processed;

        if ($ids->isEmpty() || $processed >= $this->total) {
            Session::forget('set_quantities_data');
            $this->completed = true;
            Notification::make()
                ->title('Quantités mises à jour')
                ->body("{$this->processed} produit(s) pharmacie mis à jour avec la quantité {$quantity}.")
                ->success()
                ->send();
        }
    }

    public function cancel(): void
    {
        Session::put('set_quantities_cancelled', true);
    }

    public function getProgressPercent(): int
    {
        if ($this->total <= 0) {
            return 100;
        }
        return (int) min(100, round(($this->processed / $this->total) * 100));
    }

    public function backToList(): void
    {
        Session::forget(['set_quantities_data', 'set_quantities_cancelled']);
        $this->redirect(ChemPharmacyProductResource::getUrl('index'));
    }
}
