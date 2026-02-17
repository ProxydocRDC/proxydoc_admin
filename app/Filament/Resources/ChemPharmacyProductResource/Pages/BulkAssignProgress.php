<?php

namespace App\Filament\Resources\ChemPharmacyProductResource\Pages;

use App\Filament\Resources\ChemPharmacyProductResource;
use App\Models\ChemPharmacyProduct;
use App\Models\ChemProduct;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BulkAssignProgress extends Page
{
    protected static string $resource = ChemPharmacyProductResource::class;

    protected static string $view = 'filament.resources.chem-pharmacy-product-resource.pages.bulk-assign-progress';

    protected static bool $shouldRegisterNavigation = false;

    public int $total = 0;
    public int $processed = 0;
    public int $created = 0;
    public int $skipped = 0;
    public bool $completed = false;
    public ?string $pharmacyName = null;
    public ?string $error = null;

    public function getTitle(): string
    {
        return $this->pharmacyName
            ? "Affectation en cours — {$this->pharmacyName}"
            : 'Affectation en cours';
    }

    public function mount(): void
    {
        $data = Session::get('bulk_assign_data');
        if (! $data) {
            $this->redirect(ChemPharmacyProductResource::getUrl('index'));
            return;
        }

        $this->pharmacyName = \App\Models\ChemPharmacy::find($data['pharmacy_id'])?->name ?? 'Pharmacie';
        $this->total = count($data['to_create_ids']);
        $this->skipped = $data['skipped'] ?? 0;
        $this->processed = $data['processed'] ?? 0;
        $this->created = $data['created'] ?? 0;

        // Premier lot immédiatement
        $this->processChunk();
    }

    public function processChunk(): void
    {
        $data = Session::get('bulk_assign_data');
        if (! $data) {
            $this->completed = true;
            return;
        }

        $chunkSize = 30;
        $remaining = $data['remaining_ids'] ?? $data['to_create_ids'];
        $base = $data['base'];

        if (empty($remaining)) {
            Session::forget('bulk_assign_data');
            $this->completed = true;
            $this->processed = $this->total;
            $this->created = $this->total - ($data['skipped'] ?? 0);
            return;
        }

        $chunk = array_slice($remaining, 0, $chunkSize);
        $newRemaining = array_slice($remaining, $chunkSize);

        $chunkCreated = 0;
        DB::transaction(function () use ($chunk, $base, &$chunkCreated) {
            foreach ($chunk as $pid) {
                $product = ChemProduct::find($pid);
                // Prix : formulaire > prix_ref du produit > 0
                $salePrice = filled($base['sale_price'] ?? null)
                    ? (float) $base['sale_price']
                    : ((float) ($product?->price_ref ?? 0));
                // Devise : formulaire > devise du produit > USD
                $currency = filled($base['currency'] ?? null)
                    ? $base['currency']
                    : ($product?->currency ?? 'USD');

                ChemPharmacyProduct::create(array_merge($base, [
                    'product_id'  => $pid,
                    'sale_price'  => $salePrice,
                    'currency'    => $currency,
                ]));
                $chunkCreated++;
            }
        });

        $data['remaining_ids'] = $newRemaining;
        $data['processed'] = ($data['processed'] ?? 0) + count($chunk);
        $data['created'] = ($data['created'] ?? 0) + $chunkCreated;

        Session::put('bulk_assign_data', $data);

        $this->processed = $data['processed'];
        $this->created = $data['created'];

        if (empty($newRemaining)) {
            Session::forget('bulk_assign_data');
            $this->completed = true;
            Notification::make()
                ->title('Affectation terminée')
                ->body("{$this->created} produit(s) créé(s) • {$this->skipped} ignoré(s) (déjà présents)")
                ->success()
                ->send();
        }
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
        Session::forget('bulk_assign_data');
        $this->redirect(ChemPharmacyProductResource::getUrl('index'));
    }
}
