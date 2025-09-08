<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\ChemShipmentEvent;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ShipmentMapWidget extends Widget
{
    use HasWidgetShield;
    protected static string $view     = 'filament.widgets.shipment-map-widget';
    protected static ?string $heading = 'Suivi sur carte';

    public ?int $shipmentId = null;
    // important: pas lazy pour re-render avec la page
    protected static bool $isLazy = false;
    protected $listeners          = ['setShipmentId' => 'setShipment'];

    public function setShipment(?int $id): void
    {
        $this->shipmentId = $id;
    }
    public function onShipmentChanged($id): void
    {
        $this->shipmentId = $id ? (int) $id : null;
        // rien d’autre à faire : Livewire re-render -> Blade réexécute @php ... -> la carte se réinitialise
    }
    // public function getEventsProperty()
    // {
    //     if (! $this->shipmentId) return collect();
    //     return ChemShipmentEvent::where('shipment_id', $this->shipmentId)
    //         ->orderBy('event_time')
    //         ->get()
    //         ->map(function ($e) {
    //             $lat = data_get($e->location, 'lat');
    //             $lng = data_get($e->location, 'lng');
    //             return [
    //                 'lat' => $lat ? (float) $lat : null,
    //                 'lng' => $lng ? (float) $lng : null,
    //                 'type' => $e->event_type,
    //                 'time' => optional($e->event_time)->format('d/m/Y H:i'),
    //                 'remarks' => $e->remarks,
    //             ];
    //         })
    //         ->filter(fn ($p) => $p['lat'] && $p['lng'])
    //         ->values();
    // }

    /** @return array<int, array{lat:float,lng:float,time:?string,type:?string,remarks:?string}> */
    public function getEventsProperty(): array
    {
        if (! $this->shipmentId) {
            return [];
        }

        // ⚠️ Mets ici le vrai nom de la colonne FK (ex: 'shipment_id')
        $events = ChemShipmentEvent::query()
            ->where('shipment_id', $this->shipmentId)
            ->orderBy('event_time')
            ->get();

        return $events->map(fn($e) => [
            'lat'     => (float) $e->lat,
            'lng'     => (float) $e->lng,
            'time'    => optional($e->event_time)->format('Y-m-d H:i'),
            'type'    => $e->status ?? $e->type,
            'remarks' => $e->remarks,
        ])->all();
    }
    public static function canView(): bool
    {
        return Auth::user()?->can('widget_ShipmentMapWidget') ?? false;
    }
// public static function canView(): bool
// {
//     return Gate::allows('view', static::class);
// }
}
