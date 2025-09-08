<?php
namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\ChemShipment;
use App\Models\ChemShipmentEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ShipmentsTracker extends Page implements Forms\Contracts\HasForms
{

    use Forms\Concerns\InteractsWithForms,HasPageShield;
    protected static ?string $navigationIcon  = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Logistique';
    protected static string $view             = 'filament.pages.shipments-tracker';
    protected static ?string $title           = 'Suivi des livraisons';

    public ?int $shipmentId = null;

    // ✅ Déclare un seul formulaire standard
    public function form(Form $form): Form
    {
        // return $form->schema([
        //     Forms\Components\Select::make('shipmentId')
        //         ->label('Livraison')
        //         ->options(fn() => ChemShipment::query()
        //                 ->orderByDesc('id')
        //                 ->pluck('tracking_number', 'id'))
        //         ->searchable()
        //         ->reactive()
        //         ->helperText('Choisissez une livraison à tracer sur la carte.'),
        // ]);
        return $form
            ->schema([
                Select::make('shipmentId')
                    ->label('Livraison')
                    ->options(
                        ChemShipment::query()
                            ->orderByDesc('id')
                            ->pluck('tracking_number', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        // DIT AU WIDGET QUELLE LIVRAISON EST CHOISIE
                        $this->emit('shipmentChanged', $state);
                        // (facultatif) garde la valeur en propriété locale aussi
                        $this->shipmentId = $state ? (int) $state : null;
                    }),
            ]);
    }
    // Affiche le formulaire + les widgets sans casser le "single root"
    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         \App\Filament\Widgets\ShipmentMapWidget::class,
    //     ];
    // }
    /** ✅ Demande à Filament d’insérer le widget dans la page */

    /** Rendre le widget dans la page */
    public function getWidgetData(): array
    {
        return ['shipmentId' => $this->shipmentId];
    }

    protected function getWidgets(): array
    {
        return [\App\Filament\Widgets\ShipmentMapWidget::class];
    }
    /** @return array<int, array{lat:float,lng:float,time:?string,type:?string,remarks:?string}> */
    public function getEventsProperty(): array
    {
        if (! $this->shipmentId) {
            return [];
        }

        // ⚠️ adapte le nom de la colonne FK selon ta table (ex: 'shipment_id' ou 'chem_shipment_id')
        $events = ChemShipmentEvent::query()
            ->where('shipment_id', $this->shipmentId) // <-- mets le bon nom ici
            ->orderBy('event_time')
            ->get();

        return $events->map(function ($e) {
            return [
                'lat'     => (float) $e->lat,
                'lng'     => (float) $e->lng,
                'time'    => optional($e->event_time)->format('Y-m-d H:i'),
                'type'    => $e->status ?? $e->type, // adapte au champ réel
                'remarks' => $e->remarks,
            ];
        })->all();
    }
    public static function canAccess(): bool
    {
        return Auth::user()?->can('page_ShipmentsTracker') ?? false;
    }
    // public static function canAccess(): bool
    // {
    //     // Demande à Laravel : "peut-on 'view' cette page ?"
    //     return Gate::allows('view', static::class);
    // }
}
